<?php

namespace Proxy\Crons;

use Proxy\Assignment\Port\IPv4\Port;
use Vendor\ParallelCurl;

/**
 * Class ProxyCheckConnect, based on "proxychecker.php"
 *
 * @package Reseller\Crons
 */
class ProxyCheckConnectCron extends AbstractDefaultSettingsCron
{
    protected $config = [
        'enabled' => false
    ];

    protected $settings = [
        'url'            => 'www.bing.com',
        'dryRun'         => true,
        'iterations'     => 250,
        'perIteration'   => 50,
        'checkAll'       => false,
        'checkAfterDate' => '2016-12-06',

        // Curl settings
        'perSecond'      => 40,
        'timeout'        => 20,
    ];

    public function run()
    {
        // error_log("Deleting Old Stats - Part1");
        // $query = "DELETE FROM proxies_ipv4_stats WHERE time <= DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 500000";
        // $db->query($query);

        // error_log("Deleting Old Stats - Part2");
        // $query = "DELETE FROM proxy_stats WHERE time <= DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 500000";
        // $db->query($query);

        $pch = new ParallelCurl($this->getSetting('perSecond'), $this->getSetting('perSecond'), [
            CURLOPT_CONNECTTIMEOUT => $this->getSetting('timeout'),
            CURLOPT_TIMEOUT => $this->getSetting('timeout'),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_FAILONERROR => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Cache-Control: no-cache"],
            CURLOPT_FORBID_REUSE => true,
        ]);

        $totalResults = 0;
        $timeoutResults = 0;
        $otherResults = 0;

        $activatedProxies = [];
        $errorProxies = [];

        for ($i = 0; $i < $this->getSetting('iterations'); $i++ ) {
            $sqlBind = [];
            $this->output(sprintf('# Iteration %s/%s', $i + 1, $this->getSetting('iterations')));

            if ($this->getSetting('checkAll') or !$this->getSetting('checkAfterDate')) {
                $sql = "SELECT *
                    FROM (
                        SELECT *
                        FROM proxies_ipv4
                        WHERE (pcheck = 1 or active = 1 or new = 1)
                        ORDER BY last_check, rand()
                    ) as pr
    
                    #GROUP BY source
                    ORDER BY IF(new, 0, 1), last_check
                    LIMIT " . $this->getSetting('perIteration');
            }
            else {
                $sql = $sql = "SELECT * 
                    FROM proxies_ipv4             
                    WHERE new = 1 and date_added >= ? and dead = 0       
                    ORDER BY last_check
                    LIMIT " . $this->getSetting('perIteration');
                $sqlBind[] = $this->getSetting('checkAfterDate');
            }

            $proxies = $this->getConn()->executeQuery($sql, $sqlBind);
            while($proxy = $proxies->fetch(\PDO::FETCH_OBJ)){
                $this->output("Testing proxy \"{$proxy->ip}:{$proxy->port}\"...");
                $pch->startRequest($this->getSetting('url'), function ($response, $url, $curl_handle, $params)
                use (&$timeoutResults, &$otherResults, &$errorProxies, &$activatedProxies) {
                    $error = curl_error($curl_handle);
                    $curl_http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
                    $download_size = curl_getinfo($curl_handle, CURLINFO_SIZE_DOWNLOAD);

                    $deadCheck = false;
                    if($error) {
                        $deadCheck = true;
                        if(preg_match('~timed?[\s\-]out~i', $error)) {
                            $timeoutResults++;
                            $this->output('TIMEOUT: ' . json_encode([
                                'error'    => $error,
                                'httpCode' => $curl_http_code,
                                'size'     => $download_size,
                                'source'   => $params->source,
                                'ip'       => $params->ip,
                            ]));
                        } else {
                            $otherResults++;
                            $this->output('ERROR: ' . json_encode([
                                'error'    => $error,
                                'httpCode' => $curl_http_code,
                                'size'     => $download_size,
                                'source'   => $params->source,
                                'ip'       => $params->ip,
                            ]));
                        }
                    } else {
                        $total_time = curl_getinfo($curl_handle, CURLINFO_TOTAL_TIME);
                        $this->output('OK: ' . json_encode([
                            'httpCode' => $curl_http_code,
                            'size'     => $download_size,
                            'time'     => $total_time,
                            'source'   => $params->source,
                            'ip'       => $params->ip,
                        ]));
                    }

                    if (!$this->getSetting('dryRun')) {

                        $update = [
                            'last_check' => date('Y-m-d H:i:s'),
                            'last_error' => $error
                        ];

                        $deadTotalCount = $params->dead_total_count;
                        if ($deadCheck) {
                            if ($params->dead_count >= 1) {
                                $shouldNotify = false;
                                if (!$params->dead) {
                                    $shouldNotify = true;
                                    $update['dead_total_count'] = ++$deadTotalCount;
                                }
                                $update['dead'] = $params->country == Port::COUNTRY_US ? 1 : 0;
                                $update['dead_count'] = $params->dead_count + 1;
                                $update['dead_date'] = date('Y-m-d H:i:s');
                            } else {
                                $shouldNotify = false;
                                $update[ 'dead' ] = 0;
                                $update['dead_count'] = $params->dead_count + 1;
                            }
                        } else {
                            if ($params->new == 1) {
                                $update['active'] = 1;
                                $update['new'] = 0;
                                $activatedProxies[$params->source][] = $params;
                            }
                            $shouldNotify = false;
                            $update['dead'] = 0;
                            $update['dead_count'] = 0;
                        }

                        $this->getConn()->update('proxies_ipv4', $update, [ 'id' => $params->id ]);

                        if ($shouldNotify) {
                            $errorProxies[$params->source][] = [
                                'proxy' => $params,
                                'error' => $error
                            ];
                        }
                    }
                    else {
                        $this->getConn()->update('proxies_ipv4', [
                            'last_check' => date('Y-m-d H:i:s'),
                            'last_error' => $error
                        ], [ 'id' => $params->id ]);
                    }
                }, $proxy, $proxy->ip. ":" . $proxy->port);
                $totalResults++;
            }

            $pch->finishAllRequests();
        }

        $this->output("TOTAL COUNT:" . $totalResults);
        $this->output("TIMEOUT COUNT:" . $timeoutResults);
        $this->output("ERROR COUNT:" . $otherResults);

        if($errorProxies) {
            foreach($errorProxies as $source => $ms) {
                $subject = count($ms) . ' Dead Proxies [' . $source . ']';
                $message = '';
                foreach($ms as $m) {
                    $message .= $m['proxy']->ip . ":" . $m['proxy']->port . ":" . $m['error'] . PHP_EOL;
                }

                $this->alertEmail($subject, $message);
            }
        }

        if($activatedProxies) {
            foreach($activatedProxies as $source => $ms) {
                $subject = count($ms) . ' Activated Proxies [' . $source . ']';
                $message = '';
                foreach($ms as $m) {
                    $message .= $m->ip . ":" . $m->port . PHP_EOL;
                }

                $this->alertEmail($subject, $message);
            }
        }

        return true;
    }
}
