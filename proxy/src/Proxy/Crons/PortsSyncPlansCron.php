<?php

namespace Proxy\Crons;

use Proxy\Assignment\PackageDict;
use Proxy\Assignment\Port;
use Proxy\Assignment\PortAssigner;

/**
 * Class PortsSyncPlansCron based on "assignPorts.php" and "assignPortsReseller.php"
 *
 * @package Reseller\Crons
 */
class PortsSyncPlansCron extends AbstractCron
{
    protected $settings = [
        'dryRun' => false,
        'ignoreUsers' => [
            'decrease' => [
                // Instagress
                3133,
                // User auth emulator
                -2
            ],
            'increase' => []
        ]
    ];

    public function run()
    {
        $assigner = new PortAssigner($this->getConn(), $this->logger);
        $ignoreDecreaseUsers = $this->getSetting('ignoreUsers.decrease');

        // Sync Packages quantity & status (freeze, unfreeze)
        $query = "
            SELECT 
              pup.id, pup.status, 
              pup.ports as package_count, upf.count as frozen_count, up.count as current_count,
              pup.created
            FROM proxy_user_packages pup
            LEFT JOIN (
                SELECT package_id, count(*) as count
                FROM user_ports_frozen
                GROUP BY package_id
            ) upf ON pup.id = upf.package_id
            LEFT JOIN (
                SELECT user_id, country, category, count(*) as count
                FROM user_ports
                WHERE user_type = :clientType
                GROUP BY user_id, country, category
            ) as up ON pup.user_id = up.user_id and pup.country = up.country and pup.category = up.category
            WHERE pup.ports > 0 AND pup.ip_v = :ipv4
              AND ( 
                  (pup.status = :statusActive AND upf.count > 0) OR
                  (pup.status = :statusSuspended AND IFNULL(upf.count, 0) = 0) OR
                  (pup.ports != IFNULL(up.count, 0) AND IFNULL(upf.count, 0) = 0 AND pup.status = :statusActive)
              )
        ";
        $rows = $this->getConn()->executeQuery($query, [
            'clientType' => Port\IPv4\Port::TYPE_CLIENT,
            'statusActive' => PackageDict::STATUS_ACTIVE,
            'statusSuspended' => PackageDict::STATUS_SUSPENDED,
            'ipv4'            => Port\IPv4\Port::INTERNET_PROTOCOL
        ])->fetchAll();
        if ($rows) {
            $this->log('Syncing IPv4 Packages statuses & quantity: ' . count($rows) . ' packages',
                ['packages' => $rows]);

            foreach ($rows as $row) {
                $assigner->syncPackage($row['id'], $this->getSetting('dryRun'), $ignoreDecreaseUsers);
            }
        }

        $query = "
            SELECT 
            pup.id, pup.status, 
            pup.ports as package_count, up.count as current_count,
            pup.created
            FROM proxy_user_packages pup
            LEFT JOIN (
                SELECT package_id, count(*) as count
                FROM user_ports_ipv6
                GROUP BY package_id
            ) as up ON pup.id = up.package_id
            WHERE pup.ports > 0 AND pup.ip_v = :ipv6
              AND ( 
                  (pup.ports != IFNULL(up.count, 0) AND pup.status = :statusActive)
              )
        ";
        $rows = $this->getConn()->executeQuery($query, [
            'statusActive' => PackageDict::STATUS_ACTIVE,
            'ipv6'         => Port\IPv6\Package::INTERNET_PROTOCOL
        ])->fetchAll();
        if ($rows) {
            $this->log('Syncing IPv6 Packages statuses & quantity: ' . count($rows) . ' packages',
                ['packages' => $rows]);

            foreach ($rows as $row) {
                $assigner->syncPackage($row['id'], $this->getSetting('dryRun'), $ignoreDecreaseUsers);
            }
        }

        // Sync Packages ~> Ports (add or remove ports, legacy method)
        $query = "
            SELECT pup.user_id as id, :clientType as user_type,  pup.country, pup.category, 
              IFNULL(pup.ports, IFNULL(pp.ports, 0)) as total_port_count,
              IFNULL(ports.count, 0) as actual_port_count,
              pup.replacements, pup.status, pup.created
            FROM proxy_user_packages pup 
            INNER JOIN proxy_users pu ON pu.id = pup.user_id
            INNER JOIN proxy_packages pp ON pup.package_id = pp.id
            LEFT JOIN (
                SELECT user_id, country, category, count(*) as count
                FROM user_ports
                WHERE user_type = :clientType
                GROUP BY user_id, country, category
            ) as ports ON pup.user_id = ports.user_id and pup.country = ports.country and pup.category = ports.category
            WHERE IFNULL(pup.ports, IFNULL(pp.ports, 0)) != IFNULL(ports.count, 0) 
            AND pup.status = :statusActive
            AND pup.ip_v = :ipv4

            UNION ALL

            SELECT ru.id, :resellerType as user_type, rup.country, rup.category,  IFNULL(rup.count, 0) as total_port_count,
              IFNULL(ports.count, 0) as actual_port_count,
              rup.replacements, 'active' as status, rup.created
            FROM reseller_users ru
            JOIN reseller_user_packages rup ON rup.reseller_user_id = ru.id
            LEFT JOIN (
                SELECT user_id, country, category, count(*) as count
                FROM user_ports
                WHERE user_type = :resellerType
                GROUP BY user_id, country, category
            ) as ports ON ru.id = ports.user_id and rup.country = ports.country and rup.category = ports.category
            WHERE IFNULL(rup.count, 0) != IFNULL(ports.count, 0)";
        $portAlignments = $this->getConn()->fetchAll($query, [
            'clientType' => Port\IPv4\Port::TYPE_CLIENT,
            'resellerType' => Port\IPv4\Port::TYPE_RESELLER,
            'statusActive' => PackageDict::STATUS_ACTIVE,
            'ipv4'         => Port\IPv4\Port::INTERNET_PROTOCOL
        ]);

        if (count($portAlignments)) {
            $this->log('Syncing Packages ~> Ports: ' . count($portAlignments) . ' packages',
                ['packages' => $portAlignments]);
        }

        foreach($portAlignments as $portAlignment) {
            if (empty($portAlignment['id'])) {
                $this->warn('No user found for package', ['package' => $portAlignment]);

                continue;
            }

            /** @var Port\IPv4\CountedPort $port */
            $port = Port\IPv4\CountedPort::construct()
                ->setUserId($portAlignment['id'])
                ->setCountry($portAlignment['country'])
                ->setCategory($portAlignment['category'])
                ->setTotalPortCount($portAlignment['total_port_count'])
                ->setActualPortCount($portAlignment['actual_port_count'])
                ->setUserType($portAlignment['user_type']);

            // Ignore decrease
            if (in_array($port->getUserId(), $ignoreDecreaseUsers) and
                $port->getTotalPortsCount() < $port->getActualPortCount()) {
                $this->warn(sprintf('Ignored decrement for %s %s-%s: ', $port->getUserId(),
                    $port->getCountry(), $port->getCategory()), ['package' => $portAlignment],
                    ['userId' => $portAlignment[ 'lid' ]]);

                continue;
            }

            $assigner->alignPortsCounted($port, true, true, $this->getSetting('dryRun'));
        }

        // Sync Ports ~> Packages (remove ports if no package exists)
        $portAlignments = $this->getConn()->fetchAll("            
            SELECT up.user_id, :clientType as user_type, up.country, up.category, count(*) as actual_count, :ipv4 as ip_v             
            FROM user_ports up
            LEFT JOIN proxy_user_packages pup ON pup.category = up.category and pup.country = up.country and
              pup.user_id = up.user_id
            WHERE up.user_type = :clientType AND pup.id IS NULL
            GROUP BY up.user_id, up.country, up.category

            UNION ALL

            SELECT up.user_id, :resellerType as user_type, up.country, up.category, count(*) as actual_count, :ipv4 as ip_v            
            FROM user_ports up
            LEFT JOIN reseller_user_packages rup ON rup.reseller_user_id = up.user_id AND rup.country = up.country AND rup.category = up.category
            WHERE up.user_type = :resellerType AND rup.count IS NULL
            GROUP BY up.user_id, up.country, up.category", [
            'clientType' => Port\IPv4\Port::TYPE_CLIENT,
            'resellerType' => Port\IPv4\Port::TYPE_RESELLER,
            'ipv4'         => Port\IPv4\Port::INTERNET_PROTOCOL
        ]);

        $data = $this->getConn()->fetchAll("
            SELECT up.user_id, up.package_id, count(*) as actual_count, :ipv6 as ip_v
            FROM user_ports_ipv6 up
            LEFT JOIN proxy_user_packages pup ON pup.id = up.package_id
            WHERE pup.id IS NULL            
            GROUP BY up.package_id
        ", ['ipv6' => Port\IPv6\Package::INTERNET_PROTOCOL]);
        $portAlignments = array_merge($portAlignments, $data);

        if (count($portAlignments)) {
            $this->log('Syncing Ports ~> Packages: ' . count($portAlignments) . ' packages',
                ['packages' => $portAlignments]);
        }

        foreach ($portAlignments as $portType) {
            if (empty($portType['user_id'])) {
                $this->warn('No user found for package', ['package' => $portType]);

                continue;
            }

            if (Port\IPv4\Port::INTERNET_PROTOCOL == $portType['ip_v']) {
                $port = Port\IPv4\CountedPort::construct()
                    ->setUserId($portType['user_id'])
                    ->setCountry($portType['country'])
                    ->setCategory($portType['category'])
                    ->setTotalPortCount(0)
                    ->setActualPortCount($portType['actual_count'])
                    ->setUserType($portType['user_type']);
            }
            elseif (Port\IPv6\Package::INTERNET_PROTOCOL == $portType['ip_v']) {
                $port = Port\IPv6\AggregatedPorts::construct()
                    ->setUserId($portType['user_id'])
                    ->setPackageId($portType['package_id'])
                    ->setTotalPortCount(0)
                    ->setActualPortCount($portType['actual_count']);
            }
            else {
                $this->warn('No internet protocol is known for package', ['package' => $portType]);
                continue;
            }

            // Ignore decrease
            if (in_array($port->getUserId(), $ignoreDecreaseUsers)) {
                $this->warn(sprintf('Ignored decrement for %s %s-%s: ', $port->getUserId(),
                    $port->getCountry(), $port->getCategory()), ['package' => $portType],
                    ['userId' => $port->getUserId()]);

                continue;
            }

            $assigner->alignPortsCounted($port, false, true, $this->getSetting('dryRun'));
        }

        // Remove orphan frozen ports
        $packages = $this->getConn()->executeQuery("
            SELECT upf.package_id, upf.user_id
            FROM user_ports_frozen upf
            LEFT JOIN proxy_user_packages pup ON upf.package_id = pup.id
            WHERE pup.id IS NULL
            GROUP BY upf.package_id");

        if ($packages->rowCount()) {
            $this->log('Cleaning up frozen ports: ' . $packages->rowCount() . ' packages');

            while ($row = $packages->fetch()) {
                $this->debug('Removed frozen ports', [
                    'ports' => $this->getConn()->executeQuery('SELECT * FROM user_ports_frozen WHERE package_id = ?',
                        [$row['package_id']])->fetchAll()], ['userId' => $row['user_id']]);
                $this->getConn()->delete('user_ports_frozen', ['package_id' => $row['package_id']]);
            }
        }

        return true;
    }
}
