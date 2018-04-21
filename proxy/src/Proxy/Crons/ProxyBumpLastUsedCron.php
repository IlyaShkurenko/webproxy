<?php

namespace Proxy\Crons;

/**
 * Class ProxyBumpLastUsedCron based on "statics.php"
 *
 * @package Reseller\Crons
 */
class ProxyBumpLastUsedCron extends AbstractCron
{

    protected $config = [
        'schedule' => '0 * * * *'
    ];

    public function run()
    {
        $this->getConn()->executeUpdate("
            UPDATE proxies_ipv4
            SET last_used = NOW()
            WHERE id IN (SELECT proxy_ip FROM user_ports)");

        return true;
    }
}
