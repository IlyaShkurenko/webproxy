<?php

namespace Proxy\Crons;

use Axelarge\ArrayTools\Arr;
use Vendor\ParallelCurl;

/**
 * Class ProxyCheckGeo base on "getIPInfo.php"
 *
 * @package Reseller\Crons
 */
class ProxyCheckGeoCron extends AbstractCron
{
    protected $config = [
        'schedule' => '0 * * * *',
        'enabled' => false
    ];

    protected $settings = [
        'url'          => 'http://ip-api.com/json/%s',
        'dataMapping'  => [
            'country' => 'countryCode',
            'region'  => 'region',
            'city'    => 'city'
        ],
        'wait'         => 5,
        'proxiesCount' => 150,
        'dryRun'       => false,

        // Curl
        'concurrent'   => 1,
        'perSecond'    => 1,
        'timeout'      => 5 * 60,
    ];

    public function run()
    {
        $pch = new ParallelCurl($this->getSetting('concurrent'), $this->getSetting('perSecond'), [
            CURLOPT_CONNECTTIMEOUT => $this->getSetting('timeout'),
            CURLOPT_TIMEOUT        => $this->getSetting('timeout'),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_HEADER         => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36'
        ]);

        $sql     = "SELECT * FROM proxies_ipv4 WHERE active = 1 AND dead = 0 AND dead_count = 0 ORDER BY last_ip_check, id LIMIT ?";
        $proxies = $this->getConn()->executeQuery($sql, [$this->getSetting('proxiesCount')], [\PDO::PARAM_INT]);
        while ($proxy = $proxies->fetch(\PDO::FETCH_OBJ)) {
            $url = sprintf($this->getSetting('url'), $proxy->ip);
            $this->debug("Requesting \"$url\"...");
            $pch->startRequest($url, function ($response, $url, $curl_handle, $params) {

                if ($response) {
                    $mapping = $this->getSetting('dataMapping');
                    $r = json_decode($response, true);
                    $data = [];
                    foreach ($mapping as $what => $from) {
                        $data[$what] = strtolower(Arr::getNested((array) $r, $from));
                    }

                    if (!empty($data['country'])) {
                        $this->debug('OK', [
                            'id'      => $params->id,
                            'ip'      => $params->ip,
                            'port'    => $params->port,
                            'country' => $data['country'],
                            'region'  => $data['region'],
                            'city'    => $data['city']
                        ]);

                        if (!$this->getSetting('dryRun')) {
                            $sql = "UPDATE proxies_ipv4
                                SET ip_country_code = ?, ip_region_code = ?, ip_city = ?, last_ip_check = NOW()
                                WHERE id = ?";
                            $this->getConn()->executeQuery(
                                $sql,
                                [$data['country'], $data['region'], $data['city'], $params->id],
                                [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT]
                            );
                        }
                    }
                    else {
                        $this->warn('NO DATA FOUND', [
                            'id'       => $params->id,
                            'ip'       => $params->ip,
                            'port'     => $params->port,
                            'country'  => $data[ 'country' ],
                            'region'   => $data[ 'region' ],
                            'city'     => $data[ 'city' ],
                            'response' => $r,
                            'raw'      => $response
                        ]);
                    }

                } else {
                    $this->warn('FAIL', [
                            'id'      => $params->id,
                            'ip'      => $params->ip,
                            'port'    => $params->port,
                            'error' => curl_error($curl_handle),
                        ]);
                }
            }, $proxy, "{$proxy->ip}:{$proxy->port}");

            // Wait some timeout
            sleep($this->getSetting('wait'));
        }
        $pch->finishAllRequests();

        return true;
    }
}
