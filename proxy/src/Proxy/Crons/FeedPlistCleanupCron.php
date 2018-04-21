<?php

namespace Proxy\Crons;

use Application\Helper;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\FeedBoxFactory;

/**
 * Class FeedPlistCron based on "plist.update.php"
 *
 * @package Reseller\Crons
 */
class FeedPlistCleanupCron extends AbstractDefaultSettingsCron
{
    protected $config = [
        'schedule' => '0 0 * * *',
    ];

    public function run()
    {
        $feedBox = FeedBoxFactory::build();

        $stmt = $this->getConn()->executeQuery("SELECT * FROM `proxy_source`
            LEFT JOIN
            (SELECT proxies_ipv4.source_id FROM `proxies_ipv4` WHERE proxies_ipv4.new = 1 or proxies_ipv4.active = 1 GROUP BY proxies_ipv4.source_id) a2 ON a2.source_id = proxy_source.id
            LEFT JOIN
            (SELECT proxies_ipv4.source_id FROM `proxies_ipv4` JOIN `user_ports` ON `user_ports`.proxy_ip = proxies_ipv4.id GROUP BY proxies_ipv4.source_id) a3 ON a3.source_id = proxy_source.id
            WHERE (a2.source_id IS NULL or a3.source_id IS NULL) and proxy_source.ip != 'No Source IP'");

        while ($row = $stmt->fetch()) {
            $key = "ip.{$row['ip']}";

            $feedBox->startPartialQueue($key);

            $this->debug('server (ip ' . $row['ip'] . ') has now ips on it, removing file');
        }

        $feedBox->endAllPartialQueues();
        return true;
    }
}
