<?php

namespace Proxy\Crons;

use Doctrine\DBAL\Connection;
use Proxy\Assignment\Port\IPv4\Port;

/**
 * Class UserResetReplacesCounterCron based on "statics.php"
 *
*@package Reseller\Crons
 */
class UserResetReplacesCounterCron extends AbstractCron
{

    protected $config = [
        'schedule' => '* * * * *'
    ];

    protected $settings = [
        'intervalDays' => 30
    ];

    public function run()
    {
        $categories = [
            Port::toOldCategory(Port::CATEGORY_DEDICATED),
            Port::CATEGORY_SEMI_DEDICATED,
            Port::CATEGORY_SNEAKER
        ];

        $count = $this->getConn()->executeUpdate("
            UPDATE proxy_user_packages
            SET replacements = 0, rotation = NOW()
            WHERE
              ip_v = :ipv4 AND category IN (:categories) AND
              (
                (rotation AND rotation <= DATE_SUB(NOW(), INTERVAL :period DAY)) OR
                (!rotation IS NULL AND created <= DATE_SUB(NOW(), INTERVAL :period DAY))
              )",
            ['period' => $this->getSetting('intervalDays'), 'ipv4' => Port::INTERNET_PROTOCOL, 'categories' => $categories],
            ['period' => \PDO::PARAM_INT, 'categories' => Connection::PARAM_STR_ARRAY]
        );

        if ($count) {
            $this->output("Counter reset for $count BL packages");
        }

        $count = $this->getConn()->executeUpdate("
            UPDATE reseller_user_packages
            SET replacements = 0, rotation = NOW()
            WHERE
              category IN (:categories) AND
              (
                rotation AND rotation <= DATE_SUB(NOW(), INTERVAL :period DAY) OR
                (!rotation IS NULL AND created <= DATE_SUB(NOW(), INTERVAL :period DAY))
              )",
            ['period' => $this->getSetting('intervalDays'), 'categories' => $categories],
            ['period' => \PDO::PARAM_INT, 'categories' => Connection::PARAM_STR_ARRAY]
        );

        if ($count) {
            $this->output("Counter reset for $count RS packages");
        }

        return true;
    }
}