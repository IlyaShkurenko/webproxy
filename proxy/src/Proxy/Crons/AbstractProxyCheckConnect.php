<?php

namespace Proxy\Crons;

use ErrorException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use RuntimeException;

abstract class AbstractProxyCheckConnect extends AbstractDefaultSettingsCron
{
    const CODE_WRONG_BODY = 1;

    protected $_checkConnectSettings = [
        'check' => [
            'url'         => 'http://www.bing.com/robots.txt',
            // Limits, connection (all of these are used in internal methods)
            'loadBody'    => true,
            'concurrency' => 500,
            'timeout'     => [
                'connect' => 3,
                'load'    => 3
            ],
            // Whether check ip from the response (external ip should be equal proxy ip)
            'checkBody'   => true,
        ]
    ];

    protected $emailQueue = [];

    protected function loadSettings()
    {
        return $this->getMergedDataWithClassConfig(parent::loadSettings(), $this->_checkConnectSettings, __CLASS__);
    }

    protected function prepareClassDataClassConfig(array $config)
    {
        return ($this->getDiffDataWithClassConfig(
            parent::prepareClassDataClassConfig($config), __CLASS__, function (array $config) {
            return ['settings' => $config];
        }));
    }

    protected function buildHttpClient()
    {
        return new Client([
            'base_uri'        => $this->getSetting('check.url'),
            'connect_timeout' => $this->getSetting('check.timeout.connect'),
            'timeout'         => $this->getSetting('check.timeout.load'),
            'curl'            => [
                // Connect
                CURLOPT_FAILONERROR    => true,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,

                CURLOPT_NOBODY        => !$this->getSetting('check.loadBody'),

                // No cache
                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_FORBID_REUSE  => true
            ]
        ]);
    }

    protected function checkProxyByRows(Client $httpClient, array $rows, $countProcessed, $countAll, callable $callback)
    {
        $queue = [];

        foreach ($rows as $row) {
            // Skip 0 value
            $countProcessed++;

            $queue[] = function () use ($httpClient, $row, $countAll, $countProcessed, $callback) {
                $promise = $httpClient->getAsync('', ['proxy' => "http://{$row['ip']}:{$row['port']}"]);
                $promise->then(function (Response $response) use ($row, $countAll, $countProcessed, $callback) {
                    try {
                        $resolvedAs = '';

                        if ($this->getSetting('check.loadBody')) {
                            $content = (string) $response->getBody();

                            if (!$content) {
                                throw new ErrorException('Empty body in response', self::CODE_WRONG_BODY);
                            }

                            if ($this->getSetting('check.checkBody')) {
                                if (false === strpos($content, $row[ 'ip' ])) {
                                    if (filter_var($content, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                                        $resolvedAs = $content;
                                    }
                                    else {
                                        throw new ErrorException('Wrong body received: "' . substr($content, 0,
                                                50) . '"',
                                            self::CODE_WRONG_BODY);
                                    }
                                }
                            }
                        }

                        $this->output("Proxy {$row['ip']} $countProcessed/$countAll - OK" .
                            (!$resolvedAs ? '' : ", resolved as \"$resolvedAs\""));
                    }
                    catch (Exception $e) {
                        $this->output("Proxy {$row['ip']} $countProcessed/$countAll - ERR: " . $e->getMessage());
                        $callback($e, $row);
                    }

                    // Can throw an exception potentially
                    if (!isset($e)) {
                        $callback(null, $row);
                    }
                }, function (RequestException $e) use ($row, $countAll, $countProcessed, $callback) {
                    $this->output("Proxy {$row['ip']} $countProcessed/$countAll - ERR: " . $e->getMessage());
                    $callback($e, $row);
                });

                return $promise;
            };
        }

        $pool = new Pool($httpClient, $queue, ['concurrency' => $this->getSetting('check.concurrency')]);

        $promise = $pool->promise();
        $promise->wait();
    }

    // Email logs

    protected function emailQueueDead(array $proxyData, $error)
    {
        $this->emailQueue['dead'][$proxyData['source']][] = [
            'data' => $proxyData,
            'error' => $error
        ];
    }

    protected function emailQueueActivated(array $proxyData)
    {
        $this->emailQueue['activated'][$proxyData['source']][] = $proxyData;
    }

    protected function emailQueueNotDead(array $proxyData)
    {
        $this->emailQueue['not-dead'][$proxyData['source']][] = $proxyData;
    }

    protected function emailQueue($type, $data)
    {
        $this->emailQueue[$type][] = $data;
    }

    protected function emailQueuePush()
    {
        if (!$this->emailQueue) {
            return;
        }

        foreach ($this->emailQueue as $type => $emails) {
            $this->emailSend($type, $emails);
        }

        $this->emailQueue = [];
    }

    protected function emailSend($type, $emails)
    {
        switch ($type) {
            case 'dead':
                foreach($emails as $source => $proxies) {
                    $subject = count($proxies) . ' Dead Proxies [' . $source . '] - new proxy checker';
                    $message = '';
                    foreach($proxies as $proxy) {
                        $message .= $proxy['data']['ip'] . ":" . $proxy['data']['port'] . " - " . $proxy['error'] .
                            ', found dead ' . $proxy['data']['dead_count'] . ' times' . PHP_EOL;
                    }

                    $this->alertEmail($subject, $message);
                }
                break;

            case 'activated':
                foreach($emails as $source => $proxies) {
                    $subject = count($proxies) . ' Activated Proxies [' . $source . '] - new proxy checker';
                    $message = '';
                    foreach($proxies as $proxy) {
                        $message .= $proxy['ip'] . ":" . $proxy['port'] . PHP_EOL;
                    }

                    $this->alertEmail($subject, $message);
                }
                break;

            case 'not-dead':
                foreach($emails as $source => $proxies) {
                    $subject = count($proxies) . ' Not Dead Proxies [' . $source . '] - new proxy checker';
                    $message = '';
                    foreach($proxies as $proxy) {
                        $message .= $proxy['ip'] . ":" . $proxy['port'] .
                            ', was dead ' . $proxy['dead_count'] . ' times' . PHP_EOL;
                    }

                    $this->alertEmail($subject, $message);
                }
                break;

            default:
                throw new RuntimeException("Email type \"$type\" is unknown");
        }
    }
}
