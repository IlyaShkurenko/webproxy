<?php

namespace Proxy\Crons;

use Common\Events\Events\AbstractEventWithResult;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\Events\CheckPortsAssignment;

/**
 * Class PortsReassignRotatingProxiesCron based on "rotates.php"
 *
 * @package Reseller\Crons
 */
class PortsReassignRotatingProxiesCron extends PortsAssignProxiesCron
{
    public static $startTime;
    public static $prevTime;

    public function run()
    {
        $query = "SELECT up.*, pr.dead, pr.ip as pip, pr.port as pport, pending_replace, preg.country as region_country,
            pu.reseller_id
            FROM user_ports up
            LEFT JOIN proxies_ipv4 pr ON up.proxy_ip = pr.id
            LEFT JOIN proxy_regions preg ON up.region_id = preg.id
            LEFT JOIN proxy_users pu ON up.user_id = pu.id AND up.user_type = :userType
            WHERE category = :category
            AND (
                DATE_ADD(last_rotated, INTERVAL rotation_time MINUTE) <= :now
                OR last_rotated IS NULL
                OR dead = 1
                OR pr.active = 0
                OR pr.active IS NULL
            )
            AND (up.time_assignment_attempt <= :attemptsTimeout OR up.time_assignment_attempt IS NULL)
            ORDER BY country, category, user_type, user_id";

        $stmt = $this->getConn()->executeQuery($query, [
            'userType' => Port::TYPE_CLIENT,
            'category' => Port::toOldCategory(Port::CATEGORY_ROTATING),
            'now' => date('Y-m-d H:i:s'),
            'attemptsTimeout' => date('Y-m-d H:i:s', time() - max($this->getSetting('skipUnassignedTimeout'), 0) * 60)
        ]);

        while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $port = Port::construct()
                ->setId($row->id)
                ->setUserId($row->user_id)
                ->setUserType($row->user_type)
                ->setCountry($row->country)
                ->setCategory(Port::toNewCategory($row->category))
                ->setProxyId($row->proxy_ip);

            if (!empty($row->reseller_id)) {
                /** @var AbstractEventWithResult $event */
                $event = $this->getEvents()->emit(new CheckPortsAssignment($port, $row->reseller_id));
                if (!$event->getResult()) {
                    continue;
                }
            }

            $this->adviseAndAssignNewProxy($port);
        }

        return true;
    }
}
