<?php

namespace Proxy\Crons;

use Application\Helper;
use Exception;

class ProxyCheckConnectDead extends AbstractProxyCheckConnect
{
    protected $config = [
        'enabled' => false,
        'schedule' => '0 0 * * *',
    ];

    protected $settings = [
        'dryRun' => false,
        'queueSize' => 10000,
    ];

    public function run()
    {
        $sql = "SELECT id, ip, port, source, active, new, dead_count, dead_total_count
                FROM proxies_ipv4
                WHERE dead = 1 AND (active = 1 OR new = 1)
                ORDER BY last_check";

        Helper::queueStatement($this->getConn()->executeQuery($sql), $this->getSetting('queueSize'),
            function ($rows, $countProcessed, $countAll) {
                $this->checkProxyByRows($this->buildHttpClient(), $rows, $countProcessed, $countAll,
                    function (Exception $e = null, $row) {
                        if (!$this->getSetting('dryRun')) {
                            $update = [
                                'last_check' => date('Y-m-d H:i:s')
                            ];

                            // Still dead
                            if ($e) {
                                $update[ 'last_error' ] = $e->getMessage();
                                $update[ 'dead_count' ] = $row['dead_count'] + 1;
                            }
                            else {
                                $update[ 'dead' ] = 0;
                                $update[ 'dead_count' ] = 0;
                                $this->emailQueueNotDead($row);
                            }

                            $this->getConn()->update('proxies_ipv4', $update, ['id' => $row[ 'id' ]]);
                        }
                    });

            });

        $this->emailQueuePush();

        return true;
    }
}
