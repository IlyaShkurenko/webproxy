<?php

namespace Proxy\Crons;

use Doctrine\DBAL\Connection;
use Proxy\Assignment\Port\IPv4\Port;

/**
 * Class MonitoringNoProxiesCron based on "statics.php"
 *
 * @package Reseller\Crons
 */
class MonitoringNoProxiesCron extends AbstractDefaultSettingsCron
{
    protected $config = [
        'schedule' => '0 * * * *',
        'enabled'  => false
    ];

    protected $settings = [
        'deadStopCount' => 150
    ];

    public function run()
    {
        $count = $this->getConn()->executeQuery("
          SELECT count(*) as count FROM proxies_ipv4 WHERE dead = 1 and active = 1 and country = ?
        ", [Port::COUNTRY_US])->fetchColumn();

        if ($this->getSetting('deadStopCount') and $this->getSetting('deadStopCount') <= $count) {
            return false;
        }

        $total = 0;

        $query = "
            SELECT CONCAT(up.country, '-', up.category) as type,
                IF(region IS NOT NULL, region, \"Unknown\") as region, 
                count(*) as count
            FROM user_ports up
            LEFT JOIN proxy_users pu ON up.user_id = pu.id
            LEFT JOIN proxy_regions preg ON preg.id = up.region_id
            WHERE proxy_ip = 0 
            AND (
              ((up.country = :countryUS or up.country = :countryINTL) AND region_id != '') OR 
              !(up.country = :countryUS or up.country = :countryINTL))
            AND time_assigned <= DATE_SUB(:now, INTERVAL 30 MINUTE)
            AND category != :categorySneaker AND up.user_id > 0
            GROUP BY region, up.country, up.category

            UNION ALL

            SELECT CONCAT(up.country, '-', up.category) as type, 
              IF (up.user_type = :typeClient, pu.sneaker_location, IF(region IS NOT NULL, region, \"Unknown\")) as region, 
              count(*) as count
            FROM user_ports up
            LEFT JOIN proxy_users pu ON up.user_id = pu.id
            LEFT JOIN proxy_regions preg ON preg.id = up.region_id
            WHERE proxy_ip = 0 and time_assigned <= DATE_SUB(:now, INTERVAL 30 MINUTE)
            AND category = :categorySneaker            
            AND (up.user_type != :typeClient OR up.country != :countryUS OR pu.sneaker_location != '')
            GROUP BY pu.sneaker_location, up.country, up.category";
        $stmt  = $this->getConn()->executeQuery($query, [
            'countryUS'       => Port::COUNTRY_US,
            'countryINTL'     => Port::COUNTRY_INTERNATIONAL,
            'categorySneaker' => Port::CATEGORY_SNEAKER,
            'now'             => date('Y-m-d H:i:s'),
            'typeClient' => Port::TYPE_CLIENT,
        ]);

        if ($stmt->rowCount()) {
            $this->output(sprintf('%s users with no sneaker proxies:', $stmt->rowCount()));
        }

        $message = "!! Users Have No Proxies: !!" . PHP_EOL . PHP_EOL;
        while ($row = $stmt->fetch()) {
            $total += $row[ 'count' ];
            $message .= $row[ 'type' ] . " - " . $row[ 'region' ] . " - " . $row[ 'count' ] . PHP_EOL;
            $this->output(json_encode($row));
        }

        $query = "
            SELECT CONCAT(up.country, '-', up.category) as type, 
                IF(region IS NOT NULL, region, \"Unknown\") as region, 
                count(*) as count
            FROM user_ports up
            LEFT JOIN proxy_users pu ON up.user_id = pu.id
            LEFT JOIN proxy_regions preg ON preg.id = up.region_id
            LEFT JOIN proxies_ipv4 pr ON up.proxy_ip = pr.id
            WHERE proxy_ip != 0 AND up.user_id > 0 
            AND (
                ((DATE_ADD(last_rotated, INTERVAL rotation_time MINUTE) < :now and rotate_30 and proxy_ip > 0) AND
                  category IN(:categoriesRotated))
                OR (dead = 1 and rotate_ever = 0)
                OR (pr.active = 0 AND pr.new = 0) OR pr.id IS NULL        
                OR up.pending_replace = 1
            )
            AND category NOT IN(:categoryRotating, :categorySneaker)
            GROUP BY region, up.country, up.category
            
            UNION ALL
            
            SELECT CONCAT(up.country, '-', up.category) as type, 
              IF (up.user_type = :typeClient, pu.sneaker_location, IF(region IS NOT NULL, region, \"Unknown\")) as region, 
              count(*) as count
            FROM user_ports up
            LEFT JOIN proxy_users pu ON up.user_id = pu.id
            LEFT JOIN proxy_regions preg ON preg.id = up.region_id
            LEFT JOIN proxies_ipv4 pr ON up.proxy_ip = pr.id
            WHERE proxy_ip != 0 AND (
                (DATE_ADD(last_rotated, INTERVAL rotation_time MINUTE) < :now and rotate_30 and proxy_ip)
                OR (dead = 1 and rotate_ever = 0)
                OR pr.active != 1 OR pr.id IS NULL        
                OR up.pending_replace = 1
            )
            AND category = :categorySneaker
            AND (up.user_type != :typeClient OR up.country != :countryUS OR pu.sneaker_location != '')
            GROUP BY pu.sneaker_location, up.country, up.category";

        $stmt = $this->getConn()->executeQuery($query, [
            'now'        => date('Y-m-d H:i:s'),
            'categories' => [Port::toOldCategory(Port::CATEGORY_ROTATING)],
            'categoryRotating' => Port::toOldCategory(Port::CATEGORY_ROTATING),
            'categorySneaker' => Port::toOldCategory(Port::CATEGORY_SNEAKER),
            'categoriesRotated' => [
                Port::CATEGORY_SEMI_DEDICATED,
                Port::toOldCategory(Port::CATEGORY_DEDICATED)
            ],
            'typeClient' => Port::TYPE_CLIENT,
            'countryUS' => Port::COUNTRY_US,
        ], [
            'categories' => Connection::PARAM_STR_ARRAY,
            'categoriesRotated' => Connection::PARAM_STR_ARRAY
        ]);

        if ($stmt->rowCount()) {
            $this->output(sprintf('%s users with no proxies:', $stmt->rowCount()));
        }

        $message .= PHP_EOL . PHP_EOL . "!! Users Need Proxies for Rotations / Dead / Replacement : !!" . PHP_EOL . PHP_EOL;

        while ($row = $stmt->fetch()) {
            $total += $row[ 'count' ];
            $message .= $row[ 'type' ] . " - " . $row[ 'region' ] . " - " . $row[ 'count' ] . PHP_EOL;
            $this->output(json_encode($row));
        }

        if ($total) {
            $this->alertEmail("You need $total proxies", $message);
        }

        return true;
    }
}
