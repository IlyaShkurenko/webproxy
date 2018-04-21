<?php

namespace Proxy\Crons;

use Axelarge\ArrayTools\Arr;
use Doctrine\DBAL\Connection;

class ProxyLiberateBlocksCron extends AbstractCron
{
    protected $settings = [
        'from' => 'proxy.backend@blazingseollc.com',
        'process' => [
            [
                'blocks' => ['204.86.16.128/25'],
                'notify' => 'and.webdev@gmail.com'
            ]
        ]
    ];

    public function run()
    {
        $data = $this->getSetting('process');
        if (!array_filter($data)) {
            return true;
        }

        foreach ($data as $set) {
            if (empty($set['blocks']) or !array_filter($set['blocks'])) {
                continue;
            }

            $result = $this->getConn()->fetchAll('
                SELECT block, ip
                FROM proxies_ipv4
                WHERE active = 1 AND block IN (:blocks)
                AND id NOT IN (SELECT proxy_ip FROM user_ports)
                ORDER BY block, ip
            ', ['blocks' => $set['blocks']], ['blocks' => Connection::PARAM_STR_ARRAY]);

            // There is something to process
            if ($result) {
                $result = Arr::wrap($result)->groupBy('block')->map(function($data) { return Arr::pluck($data, 'ip'); })->toArray();
                $this->log('Liberating blocks ' . join(', ', array_keys($result)), ['blocks' => $result]);
                $this->getConn()->executeUpdate('
                    UPDATE proxies_ipv4
                    SET active = 0
                    WHERE active = 1 AND block IN (:blocks)
                    AND id NOT IN (SELECT proxy_ip FROM user_ports)
                ', ['blocks' => $set['blocks']], ['blocks' => Connection::PARAM_STR_ARRAY]);

                if (!empty($set['notify'])) {
                    $text = '';
                    foreach ($result as $block => $ips) {
                        $text .= PHP_EOL . "Block \"$block\":" . PHP_EOL;
                        foreach ($ips as $ip) {
                            $text .= "\t- $ip" . PHP_EOL;
                        }
                    }
                    mail(
                        $set['notify'],
                        'Liberating blocks ' . join(', ', array_keys($result)),
                        trim($text),
                        'From: ' . $this->getSetting('from')
                    );
                }
            }
        }

        return true;
    }
}
