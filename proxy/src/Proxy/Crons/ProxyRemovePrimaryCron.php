<?php

namespace Proxy\Crons;

/**
 * Class ProxyRemovePrimaryCron based on "statics.php"
 *
 * @package Reseller\Crons
 */
class ProxyRemovePrimaryCron extends AbstractCron
{

    protected $config = [
        'schedule' => '0 * * * *'
    ];

    public function run()
    {
        $this->getConn()->executeUpdate("DELETE FROM proxies_ipv4 WHERE ip IN (SELECT ip from proxy_source)");

        return true;
    }
}