<?php

namespace Proxy\Assignment;

use Axelarge\ArrayTools\Arr;
use Blazing\Logger\Logger;
use Doctrine\DBAL\Connection;
use Proxy\Assignment\Port\AbstractPackage;
use Proxy\Assignment\Port\AggregatedPortsInterface;
use Proxy\Assignment\Port\IPv4;
use Proxy\Assignment\Port\IPv6;
use Proxy\Assignment\Port\PortInterface;
use Proxy\Assignment\PortAssigner\ResultAlign;

class PortAssigner
{

    const MIN_PORT = 1025;
    const DEFAULT_ROTATION_TIME = 30 * 24 * 60; // 43200
    const ROTATING_ROTATION_TIME = 10;
    const GOOGLE_ROTATION_TIME = 60;

    /** @var Connection */
    protected $conn;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Connection $conn, Logger $logger = null)
    {
        $this->conn = $conn;
        $this->logger = $logger;
    }

    public function assignPortProxy(PortInterface $port, $proxyId, $updateRotated = false)
    {
        if (!$proxyId) {
            return false;
        }

        if (IPv4\Port::INTERNET_PROTOCOL == $port->getIpV()) {
            /** @var IPv4\Port $port */

            if (!$updateRotated) {
                $this->conn->update('user_ports', [
                    'proxy_ip' => $proxyId,
                    'pending_replace' => 0
                ], ['id' => $port->getId()]);
            }
            else {
                $this->conn->update('user_ports', [
                    'proxy_ip'        => $proxyId,
                    'pending_replace' => 0,
                    'last_rotated'    => date('Y-m-d H:i:s')
                ], ['id' => $port->getId()]);
            }

            if ($port->getUserType() and $port->getUserId()) {
                $this->conn->insert('proxy_user_history', [
                    'user_type' => $port->getUserType(),
                    'user_id'   => $port->getUserId(),
                    'proxy_id'  => $proxyId
                ]);
            }
            if ($port->getUserType()) {
                $this->conn->executeQuery("
              UPDATE `proxies_ipv4` SET `pristine` = 0, times_assigned = times_assigned + 1 WHERE id = ?", [$proxyId]
                );
            }

            if (IPv4\Port::CATEGORY_ROTATING == $port->getCategory() and $port->getUserType() and $port->getUserId()) {
                $this->conn->executeUpdate("
                  INSERT INTO user_proxy_stats (user_type, user_id, proxy_id, times_assigned)
                  VALUES (?, ?, ?, 1)
                  ON DUPLICATE KEY
                  UPDATE times_assigned = times_assigned + 1",
                    [$port->getUserType(), $port->getUserId(), $proxyId]);
            }
        }
        elseif (IPv6\Port::INTERNET_PROTOCOL == $port->getIpV()) {
            $this->conn->update('user_ports_ipv6', [
                'block_id' => $proxyId,
                'assigned_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $port->getId()]);
        }
        else {
            if ($this->logger) {
                $this->logger->warn('Can handle only IPv4 or IPv6 port, but "' . $port->getIpV() . '" ' .
                    'with id "' . $port->getIpV() . '" is passed');
            }

            return false;
        }


        return $proxyId;
    }

    public function syncPackage($packageId, $dryRun = false, array $usersIgnoreDecrement = [])
    {
        $package = $this->getPackage($packageId);
        $result = new ResultAlign();

        if (!$package) {
            return $result;
        }

        // Status changed
        $result = $this->syncPackageStatus($package, $usersIgnoreDecrement, $result);

        // Quantity changed
        $result = $this->syncPackageQuantity($package, $dryRun, $usersIgnoreDecrement, $result);

        return $result;
    }

    /**
     * Sync package status (check if ports should be frozen, unfrozen, removed, etc)
     *
     * @param int|AbstractPackage $packageId
     * @param array $usersIgnoreDecrement
     * @param ResultAlign|null $result
     * @return ResultAlign
     */
    protected function syncPackageStatus($packageId, array $usersIgnoreDecrement = [], ResultAlign $result = null)
    {
        $package = $this->getPackage($packageId);
        if (!$result) {
            $result = new ResultAlign();
        }
        if (!$package) {
            return $result;
        }

        // Handle only IPv4 at this time
        if (IPv4\Port::INTERNET_PROTOCOL != $package->getIpV()) {
            return $result;
        }

        $totalPorts = $this->conn->executeQuery(
            'SELECT IFNULL(pup.ports, pp.ports) as ports
        FROM proxy_user_packages pup
        INNER JOIN proxy_packages pp ON pup.package_id = pp.id
        WHERE user_id = :userId AND pup.country = :country AND pup.category = :category
        GROUP BY pup.country, pup.category', [
            'userId'   => $package->getUserId(),
            'country'  => $package->getCountry(),
            'category' => $package->getCategory()
        ])->fetchColumn();
        $currentPorts = $this->conn->executeQuery('
        SELECT count(*) FROM user_ports 
        WHERE user_type = :clientType AND user_id = :userId AND country = :country AND category = :category',
            [
                'clientType' => IPv4\Port::TYPE_CLIENT,
                'userId'     => $package->getUserId(),
                'country'    => $package->getCountry(),
                'category'   => $package->getCategory()
            ]
        )->fetchColumn();
        $frozenPorts = $this->conn->executeQuery('SELECT count(*) FROM user_ports_frozen WHERE package_id = ?',
            [$package->getId()])->fetchColumn();
        if ((PackageDict::STATUS_SUSPENDED == $package->getStatus() and !$frozenPorts) or
            PackageDict::STATUS_ACTIVE == $package->getStatus() and $frozenPorts
        ) {
            if ($this->logger) {
                $this->logger->addSharedIndex('userId', $package->getUserId());
                $this->logger->info('Status changed to "' . $package->getStatus() . '"', [
                    'package'           => $package->toArray(),
                    'currentPortsCount' => $currentPorts,
                    'frozenPortsCount'  => $frozenPorts,
                    'totalPortsCount'   => $totalPorts
                ]);
            }

            // Ignore decrease
            if (in_array($package->getUserId(), $usersIgnoreDecrement) and
                PackageDict::STATUS_SUSPENDED == $package->getStatus() and !$frozenPorts
            ) {

                if ($this->logger) {
                    $this->logger->warn(sprintf('Ignored decrement for %s %s-%s: ', $package->getUserId(),
                        $package->getCountry(), IPv4\Port::toNewCategory($package->getCategory())),
                        ['package' => $package->toArray()]);
                }
            }
            else {
                $result->mergeWith($this->processPackageStatus($package));
            }

            // Has been just processed, force list generation
            if ((PackageDict::STATUS_SUSPENDED == $package->getStatus() and $result->isDecremented()) or
                PackageDict::STATUS_ACTIVE == $package->getStatus() and $result->isIncremented()
            ) {
                $this->conn->update('proxy_users', ['preferred_format_update' => date('Y-m-d H:i:s')],
                    ['id' => $package->getUserId()]);
            }
        }

        return $result;
    }

    /**
     * Sync package quantity (check if it should be changed, create new ports, etc)
     *
     * @param int|AbstractPackage $packageId
     * @param bool $dryRun
     * @param array $usersIgnoreDecrement
     * @param ResultAlign|null $result
     * @return ResultAlign
     */
    public function syncPackageQuantity($packageId, $dryRun = false, array $usersIgnoreDecrement = [], ResultAlign $result = null)
    {
        $package = $this->getPackage($packageId);
        if (!$result) {
            $result = new ResultAlign();
        }
        if (!$package) {
            return $result;
        }

        // IPv4
        if (IPv4\Port::INTERNET_PROTOCOL == $package->getIpV()) {
            $totalPorts = $this->conn->executeQuery(
                'SELECT IFNULL(pup.ports, pp.ports) as ports
                FROM proxy_user_packages pup
                INNER JOIN proxy_packages pp ON pup.package_id = pp.id
                WHERE user_id = :userId AND pup.country = :country AND pup.category = :category
                GROUP BY pup.country, pup.category', [
                'userId'   => $package->getUserId(),
                'country'  => $package->getCountry(),
                'category' => $package->getCategory()
            ])->fetchColumn();
            $currentPorts = $this->conn->executeQuery('
                SELECT count(*) FROM user_ports 
                WHERE user_type = :clientType AND user_id = :userId AND country = :country AND category = :category',
                [
                    'clientType' => IPv4\Port::TYPE_CLIENT,
                    'userId' => $package->getUserId(),
                    'country' => $package->getCountry(),
                    'category' => $package->getCategory()]
            )->fetchColumn();
        }
        // IPv6
        else {
            $totalPorts = $package->getPorts();
            $currentPorts = $this->conn->executeQuery('SELECT count(*) FROM user_ports_ipv6 WHERE package_id = ?',
                [$package->getId()])->fetchColumn();
        }

        if (PackageDict::STATUS_ACTIVE == $package->getStatus() and $totalPorts != $currentPorts) {
            if (IPv4\Port::INTERNET_PROTOCOL == $package->getIpV()) {
                /** @var IPv4\CountedPort $aggPorts */
                $aggPorts = IPv4\CountedPort::construct()
                    ->setUserId($package->getUserId())
                    ->setCountry($package->getCountry())
                    ->setCategory($package->getCategory())
                    ->setTotalPortCount($totalPorts)
                    ->setActualPortCount($currentPorts)
                    ->setUserType(IPv4\Port::TYPE_CLIENT);
            }
            else {
                /** @var IPv6\AggregatedPorts $aggPorts */
                $aggPorts = IPv6\AggregatedPorts::convertFrom($package);
                $aggPorts->setActualPortCount($currentPorts);
            }

            if ($this->logger) {
                $this->logger->addSharedIndex('userId', $package->getUserId());
                $this->logger->info("Quantity changed from $currentPorts to $totalPorts", [
                    'package'           => $package->toArray(),
                    'currentPortsCount' => $currentPorts,
                    'totalPortsCount'   => $totalPorts
                ]);
            }

            // Ignore decrease
            if (in_array($aggPorts->getUserId(), $usersIgnoreDecrement) and
                $aggPorts->getTotalPortsCount() < $aggPorts->getActualPortCount()) {
                if ($this->logger) {
                    $this->logger->warn(sprintf('Ignored decrement for %s %s-%s: ', $aggPorts->getUserId(),
                        $aggPorts->getCountry(), $aggPorts->getCategory()), ['package' => $package->toArray()]);
                }
            }
            else {
                $result->mergeWith($this->alignPortsCounted($aggPorts, true, true, $dryRun));
            }

            if ($this->logger) {
                $this->logger->removeSharedIndex('userId');
            }
        }

        return $result;
    }

    public function alignPortsCounted(AggregatedPortsInterface $port, $increment = true, $decrement = true, $dryRun = false)
    {
        $portsCount = $port->getTotalPortsCount() - $port->getActualPortCount();
        $changed = 0;
        $result = new ResultAlign();

        if (($portsCount > 0) and $increment) {
            switch ($port->getIpV()) {

                case IPv4\Port::INTERNET_PROTOCOL:
                    /** @var IPv4\CountedPort $port */

                    $minPort = self::MIN_PORT;
                    $rotationTime = self::DEFAULT_ROTATION_TIME;

                    if (IPv4\Port::CATEGORY_ROTATING == $port->getCategory()) {
                        $rotationTime = self::ROTATING_ROTATION_TIME;
                        $maxQuery = "SELECT max(port) as port FROM user_ports WHERE user_type = ? and user_id = ?";
                        $max = $this->conn->fetchAssoc($maxQuery, [$port->getUserType(), $port->getUserId()]);
                        if (isset($max[ 'port' ])) {
                            $minPort = $max[ 'port' ] + 1; // the next one
                        }
                    }
                    elseif (IPv4\Port::CATEGORY_GOOGLE == $port->getCategory()) {
                        $rotationTime = self::GOOGLE_ROTATION_TIME;
                    }

                    // Determine region id
                    $regionId = $port->getRegionId();
                    if (!$regionId) {
                        if (IPv4\Port::TYPE_RESELLER == $port->getUserType() and in_array($port->getCategory(),
                                [IPv4\Port::CATEGORY_ROTATING])
                        ) {
                            // Mixed
                            $regionId = 1;
                        }
                        elseif (IPv4\Port::TYPE_CLIENT == $port->getUserType()) {
                            if (in_array($port->getCategory(),
                                [IPv4\Port::CATEGORY_SNEAKER, IPv4\Port::CATEGORY_SUPREME])) {
                                // Mixed
                                $regionId = 1;
                            }
                            elseif (in_array($port->getCategory(),
                                [IPv4\Port::CATEGORY_DEDICATED, IPv4\Port::CATEGORY_SEMI_DEDICATED])) {
                                $regions = $this->conn->fetchAll(
                                    'SELECT id FROM proxy_regions WHERE country = ? AND region NOT LIKE "mixed" LIMIT 2',
                                    [$port->getCountry()]);
                                $regionMixed = $this->conn->fetchColumn(
                                    'SELECT id FROM proxy_regions WHERE country = ? AND region LIKE "mixed"',
                                    [$port->getCountry()]);
                                // Single option
                                if ($regions and 1 == count($regions)) {
                                    $regionId = !$regionMixed ? $regions[ 0 ][ 'id' ] : $regionMixed;
                                }
                                elseif (!$regions and $regionMixed) {
                                    $regionId = $regionMixed;
                                }
                            }
                        }

                        for ($i = 0; $i < $portsCount; $i++) {
                            $data = [
                                'user_type'     => $port->getUserType(),
                                'user_id'       => $port->getUserId(),
                                'country'       => $port->getCountry(),
                                'category'      => IPv4\Port::toOldCategory($port->getCategory()),
                                'region_id'     => $regionId,
                                'port'          => ($port->getCategory() == IPv4\Port::CATEGORY_ROTATING) ? $minPort + $i : $minPort,
                                'time_assigned' => date('Y-m-d H:i:s'),
                                'last_rotated'  => date('Y-m-d H:i:s'),
                                'rotation_time' => $rotationTime,
                                'proxy_ip'      => 0,
                                'server_id'     => $port->getServerId(),
                                'type'          => $port->getCountry() . '-' . IPv4\Port::toOldCategory($port->getCategory())
                            ];

                            $newPort = IPv4\Port::fromArray(array_merge(['id' => ''], $data));

                            if (!$dryRun) {
                                $this->conn->insert('user_ports', $data);
                                $newPort->setId((int) $this->conn->lastInsertId());
                                $data[ 'id' ] = $newPort->getId();
                            }

                            $result->addAddedPort($newPort);

                            $changed++;
                        }
                    }
                    break;

                    case IPv6\Package::INTERNET_PROTOCOL:
                        /** @var IPv6\AggregatedPorts $port */

                        for ($i = 0; $i < $portsCount; $i++) {
                            $data = [
                                'package_id' => $port->getPackageId(),
                                'user_id' => $port->getUserId(),
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];

                            $newPort = IPv6\Port::fromArray(array_merge(['id' => ''], $data, $port->toArray()));

                            if (!$dryRun) {
                                $this->conn->insert('user_ports_ipv6', $data);
                                $newPort->setId((int) $this->conn->lastInsertId());
                                $data[ 'id' ] = $newPort->getId();
                            }

                            $result->addAddedPort($newPort);

                            $changed++;
                        }
                        break;
                }

            if ($this->logger) {
                $this->logger->debug("Added $changed ports", [
                    'totalPortsCount' => $port->getTotalPortsCount(),
                    'actualPortsCount' => $port->getActualPortCount(),
                    'rows' => array_map(function(AbstractPackage $package) { return $package->toArray(); },
                        $result->getAddedPorts())
                ], ['userId' => $port->getUserId()]);
            }
        }
        elseif (($portsCount < 0) and $decrement) {
            $absPorts = abs($portsCount);

            switch ($port->getIpV()) {

                case IPv4\Port::INTERNET_PROTOCOL:
                    $result->setRemovedPorts($this->conn->fetchAll("
                            SELECT * FROM user_ports
                            WHERE user_type = :userType and user_id = :userId
                            and country = :country and category = :category
                            ORDER BY remove_order, id DESC LIMIT :limit", [
                        'userType' => $port->getUserType(),
                        'userId'   => $port->getUserId(),
                        'country'  => $port->getCountry(),
                        'category' => IPv4\Port::toOldCategory($port->getCategory()),
                        'limit'    => $absPorts
                    ], [ 'limit' => \PDO::PARAM_INT ]));

                    if (!$dryRun) {
                        $removeQuery = "DELETE FROM user_ports
                            WHERE user_type = :userType and user_id = :userId
                            and country = :country and category = :category
                            ORDER BY remove_order, id DESC LIMIT :limit";
                        $this->conn->executeUpdate($removeQuery, [
                            'userType' => $port->getUserType(),
                            'userId'   => $port->getUserId(),
                            'country'  => $port->getCountry(),
                            'category' => IPv4\Port::toOldCategory($port->getCategory()),
                            'limit'    => $absPorts
                        ], [ 'limit' => \PDO::PARAM_INT ]);
                    }

                    break;

                case IPv6\Package::INTERNET_PROTOCOL:
                    $result->setRemovedPorts($this->conn->fetchAll("
                            SELECT * FROM user_ports_ipv6
                            WHERE package_id = :packageId
                            ORDER BY id DESC 
                            LIMIT :limit", [
                        'packageId' => $port->getPackageId(),
                        'limit'    => $absPorts
                    ], [ 'limit' => \PDO::PARAM_INT ]));

                    if (!$dryRun) {
                        $removeQuery = "DELETE FROM user_ports_ipv6
                            WHERE package_id = :packageId
                            ORDER BY id DESC 
                            LIMIT :limit";
                        $this->conn->executeUpdate($removeQuery, [
                            'packageId' => $port->getPackageId(),
                            'limit'    => $absPorts
                        ], [ 'limit' => \PDO::PARAM_INT ]);
                    }
                    break;
            }

            if ($this->logger) {
                $this->logger->debug("Removed $absPorts ports", [
                    'totalPortsCount' => $port->getTotalPortsCount(),
                    'actualPortsCount' => $port->getActualPortCount(),
                    'rows' => $result->getRemovedPorts()
                ], ['userId' => $port->getUserId()]);
            }
        }

        return $result;
    }

    /**
     * Handle package with upgraded status
     *
     * @param int|AbstractPackage $packageId
     * @return ResultAlign
     */
    protected function processPackageStatus($packageId)
    {
        $package = $this->getPackage($packageId);
        $result = new ResultAlign();

        if (!$package) {
            if ($this->logger) {
                $this->logger->warn("No package is found with id \"$packageId\"");
            }

            return $result;
        }
        if (IPv4\Port::INTERNET_PROTOCOL != $package->getIpV()) {
            $this->logger->warn("Cannot handle non-IPv4 package with id \"$packageId\"");

            return $result;
        }

        $currentPorts = $this->conn->executeQuery('
            SELECT * FROM user_ports 
            WHERE user_type = :clientType AND user_id = :userId AND country = :country AND category = :category',
            [
                'clientType' => IPv4\Port::TYPE_CLIENT,
                'userId' => $package->getUserId(),
                'country' => $package->getCountry(),
                'category' => IPv4\Port::toOldCategory($package->getCategory())
            ]
        );
        $frozenPorts = $this->conn->executeQuery('SELECT * FROM user_ports_frozen WHERE package_id = ?', [$package->getId()]);

        if ($this->logger) {
            $this->logger->addSharedIndex('userId', $package->getUserId());
            $this->logger->debug('Applying package status "' . $package->getStatus() . '"', [
                'package' => $package->toArray(),
                'currentPortsCount' => $currentPorts->rowCount(),
                'frozenPortsCount' => $frozenPorts->rowCount()
            ], ['userId' => $package->getUserId()]);
        }

        // Suspend port
        if (PackageDict::STATUS_SUSPENDED == $package->getStatus()) {
            // Common situation
            if ($currentPorts->rowCount() and !$frozenPorts->rowCount()) {
                $processed = [];
                while ($row = $currentPorts->fetch()) {
                    $data = [
                        'package_id' => $package->getId(),
                        'proxy_id'   => $row['proxy_ip'],
                        'user_id'    => $package->getUserId(),
                        'user_type'  => $row[ 'user_type' ],
                        'port_data'  => json_encode($row)
                    ];
                    $this->conn->insert('user_ports_frozen', $data);
                    $this->conn->delete('user_ports', ['id' => $row['id']]);

                    $result->addRemovedPort(IPv4\Port::fromArray($row));
                    $processed[] = Arr::except($data, ['port_data~']);
                }

                if ($this->logger) {
                    $this->logger->debug('Frozen ' . count($processed) . ' ports', ['processed' => $processed]);
                }
            }
            // Partial freeze
            elseif ($currentPorts->rowCount() and $frozenPorts->rowCount()) {
                if ($this->logger) {
                    $this->logger->warn('Partial ports freezing is not implemented', ['package' => $package->toArray()],
                        ['userId' => $package->getUserId()]);
                }
            }
            // Nothing to sync?
            elseif (!$currentPorts->rowCount() and !$frozenPorts->rowCount()) {
                if ($this->logger) {
                    $this->logger->warn('Nothing to make frozen', ['package' => $package->toArray()]);
                }
            }
            // A wrong status? It should be active then
            else {
                if ($this->logger) {
                    $this->logger->warn('Probably the package has wrong status', ['package' => $package->toArray()]);
                }
            }
        }
        elseif (PackageDict::STATUS_ACTIVE == $package->getStatus()) {
            // Common situation
            if (!$currentPorts->rowCount() and $frozenPorts->rowCount()) {
                $processed = [];
                while ($row = $frozenPorts->fetch()) {
                    $data = json_decode($row['port_data'], true);
                    if (!$data) {
                        if ($this->logger) {
                            $this->logger->warn("Unable to restore port data: " . $row['port_data'], ['port' => $row]);
                        }

                        continue;
                    }

                    $this->conn->insert('user_ports', Arr::only($data, [
                        'id', 'user_id', 'user_type', 'region_id', 'server_id', 'port', 'time_assigned', 'rotation_time',
                        'proxy_ip', 'previous_proxy_ip', 'last_rotated', 'type', 'country', 'category',
                        'remove_order'
                    ]));
                    $this->conn->delete('user_ports_frozen', ['id' => $row['id']]);

                    $result->addAddedPort(IPv4\Port::fromArray($data));
                    $processed[] = Arr::except($row, ['port_data~']);
                }

                if ($this->logger) {
                    $this->logger->debug('Unfrozen ' . count($processed) . ' ports', ['processed' => $processed]);
                }
            }
            // Partial unfreeze
            elseif ($currentPorts->rowCount() and $frozenPorts->rowCount()) {
                if ($this->logger) {
                    $this->logger->warn('Partial ports unfreezing is not implemented', ['package' => $package->toArray()],
                        ['userId' => $package->getUserId()]);
                }
            }
            // Nothing to sync?
            elseif (!$currentPorts->rowCount() and !$frozenPorts->rowCount()) {
                if ($this->logger) {
                    $this->logger->warn('Nothing to make unfrozen', ['package' => $package->toArray()]);
                }
            }
            else {
                if ($this->logger) {
                    $this->logger->warn('Probably the package has wrong status', ['package' => $package->toArray()]);
                }
            }
        }

        return $result;
    }

    /**
     * Ensure packageId is object or load it
     *
     * @param $packageId
     * @return IPv4\Port|IPv6\Package|false
     */
    protected function getPackage($packageId)
    {
        if ($packageId instanceof IPv4\Port or $packageId instanceof IPv6\Package) {
            return $packageId;
        }

        $package = $this->conn->executeQuery('SELECT * FROM proxy_user_packages WHERE id = ?', [$packageId])->fetch();
        if (!$package) {
            if ($this->logger) {
                $this->logger->warn("No package is found with id \"$packageId\"");
            }

            return false;
        }

        if (IPv4\Port::INTERNET_PROTOCOL == $package['ip_v']) {
            $object = IPv4\Port::fromArray(array_merge($package, ['user_type' => IPv4\Port::TYPE_CLIENT]));
        }
        elseif (IPv6\Package::INTERNET_PROTOCOL == $package['ip_v']){
            $object = IPv6\Package::fromArray($package);
        }
        else {
            if ($this->logger) {
                $this->logger->warn("Can handle only IPv4 or IPv6 package, but \"{$package['ip_v']}\" " .
                    "with id \"$packageId\" is passed");
            }

            return false;
        }

        return $object;
    }
}