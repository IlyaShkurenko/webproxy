<?php

namespace Proxy\Crons;

use Exception;

class ProxyCheckConnectNewCron extends AbstractProxyCheckConnect
{
    protected $config = [
        'enabled' => false
    ];

    protected $settings = [
        'emailDead' => false,
        'dryRun' => false
    ];

    public function run()
    {
        $sql = "SELECT id, ip, port, source, dead_count
                FROM proxies_ipv4
                WHERE new = 1 and dead = 0 and dead_count = 0
                ORDER BY last_check";

        $rows = $this->getConn()->fetchAll($sql);
        $this->checkProxyByRows($this->buildHttpClient(), $rows, 0, count($rows), function(Exception $e = null, $row) {
            if (!$this->getSetting('dryRun')) {
                $update = [
                    'last_check' => date('Y-m-d H:i:s')
                ];

                if ($e) {
                    $update[ 'last_error' ] = $e->getMessage();
                    $update[ 'dead_count' ] = 1;
                }
                else {
                    $update['active'] = 1;
                    $update['new'] = 0;
                    $this->emailQueueActivated($row);
                }

                $this->getConn()->update('proxies_ipv4', $update, ['id' => $row[ 'id' ]]);
            }
        });

        $this->emailQueuePush();

        return true;
    }
}