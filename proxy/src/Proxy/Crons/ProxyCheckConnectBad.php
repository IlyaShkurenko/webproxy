<?php

namespace Proxy\Crons;

use Exception;

class ProxyCheckConnectBad extends AbstractProxyCheckConnect
{
    protected $config = [
        'enabled' => false
    ];

    protected $settings = [
        'dryRun' => false,
        'workflow' => [
            1 => [
                'dead' => false,
                'timeLast' => 1 // minutes
            ],
            2 => [
                'dead' => false,
                'timeLast' => 2
            ],
            3 => [
                'dead' => false,
                'timeLast' => 3
            ]
        ],
        'deadEmailOnCount' => 10
    ];

    public function run()
    {
        $sql = "SELECT id, ip, port, source, dead_count, dead_total_count
                FROM proxies_ipv4
                WHERE dead = 0 AND dead_count = :deadCount AND DATE_ADD(last_check, INTERVAL :timeLast MINUTE) <= :now
                AND (active = 1 OR new = 1)
                ORDER BY last_check";

        $maxCount = 0;

        foreach ($this->getSetting('workflow') as $count => $config) {
            $rows = $this->getConn()->fetchAll($sql,
                ['deadCount' => $count, 'timeLast' => $config['timeLast'], 'now' => date('Y-m-d H:i:s')],
                ['timeLast' => \PDO::PARAM_INT]);

            if ($count > $maxCount) {
                $maxCount = $count;
            }

            if (!$rows) {
                continue;
            }

            $this->output("Check bad proxies which were dead \"$count\" times (" . count($rows) . ' proxies)');

            $this->checkProxyByRows($this->buildHttpClient(), $rows, 0, count($rows),
                function (Exception $e = null, $row) use ($config, $count) {
                    if (!$this->getSetting('dryRun')) {
                        $update = [
                            'last_check' => date('Y-m-d H:i:s')
                        ];

                        // Still dead
                        if ($e) {
                            $update[ 'last_error' ] = $e->getMessage();
                            $update[ 'dead_count' ] = $count + 1;

                            // Mark as dead
                            if ($config[ 'dead' ]) {
                                $update[ 'dead' ] = 1;
                                $update[ 'dead_total_count' ] = $row['dead_total_count'] + 1;
                                $update[ 'dead_date' ] = $update[ 'last_check' ];

                                $this->output("Proxy {$row['ip']} is dead, dead_count - {$update[ 'dead_count' ]}");
                            }
                            else {
                                $this->output("Proxy {$row['ip']} still in bad state, dead_count - {$update[ 'dead_count' ]}");
                            }

                            // Send email?
                            if ($count == $this->getSetting('deadEmailOnCount')) {
                                $this->emailQueueDead(array_merge($row, ['dead_count' => $update[ 'dead_count' ]]), $e->getMessage());
                            }
                        }
                        else {
                            $update[ 'dead_count' ] = 0;

                            // Don't email out on proxy restore
                            if ($this->getSetting('deadEmailOnCount') and $count > $this->getSetting('deadEmailOnCount')) {
                                $this->emailQueueNotDead($row);
                            }
                        }

                        $this->getConn()->update('proxies_ipv4', $update, ['id' => $row[ 'id' ]]);
                    }
                });
        }

        // Mark all other proxies with dead count > max as dead, just in case (human-factor)

        $sql = "SELECT id, ip, port, source, last_error, dead_count, dead_total_count
                FROM proxies_ipv4
                WHERE dead = 0 and dead_count > ?
                ORDER BY last_check";
        $stmt = $this->getConn()->executeQuery($sql, [$maxCount], [\PDO::PARAM_INT]);
        while($row = $stmt->fetch()) {
            $this->output("Proxy {$row['ip']} should be dead, so set it, dead_count - {$row[ 'dead_count' ]}");

            $this->emailQueueDead($row, $row['last_error']);
            $this->getConn()->update('proxies_ipv4',
                ['dead' => 1, 'dead_total_count' => $row[ 'dead_total_count' ] + 1, 'dead_date' => date('Y-m-d H:i:s')],
                ['id' => $row[ 'id' ]]
            );
        }

        $this->emailQueuePush();

        return true;
    }
}
