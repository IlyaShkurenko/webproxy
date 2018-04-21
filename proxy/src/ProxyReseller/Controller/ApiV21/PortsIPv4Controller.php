<?php

namespace ProxyReseller\Controller\ApiV21;

use Axelarge\ArrayTools\Arr;
use Doctrine\DBAL\Connection;
use Proxy\Assignment\Port\IPv4\Port;
use ProxyReseller\Controller\ApiV20\PortsController as BaseController;

class PortsIPv4Controller extends BaseController
{

    public function getAction($userId, $country = [], $category = [], $sortBy = [])
    {
        $user = $this->getUser($userId);
        $category = array_map(function ($category) { return Port::toOldCategory($category); }, (array) $category);
        if ($sortBy) {
            $map = [
                'id'       => 'up.id',
                'country'  => 'up.country',
                'category' => 'up.category',
                'updated'  => 'up.time_updated',
                'rotated'  => 'up.last_rotated',
                'ip'       => 'p.ip'
            ];
            $sort = [];
            foreach ($sortBy as $field => $direction) {
                $this->assertOrException(isset($map[$field]), "Field $field is not allowed ($field $direction)");
                $this->assertOrException(in_array($direction, ['asc', 'desc']),
                    "Only asc/desc direction is allowed ($field $direction)");

                $sort[] = "$map[$field] $direction";
            }

            $sortBy = $sort;
        }

        $queryParameters = [
            'userType' => Port::TYPE_CLIENT,
            'userId'   => $user[ 'id' ]
        ];
        $queryParametersTypes = [];

        $andWhere = '';
        $sortOrder = '';
        if ($country) {
            $andWhere .= " AND up.country IN (:country)";
            $queryParameters[ 'country' ] = (array) $country;
            $queryParametersTypes[ 'country' ] = Connection::PARAM_STR_ARRAY;
        }
        if ($category) {
            $andWhere .= " AND up.category IN (:category)";
            $queryParameters[ 'category' ] = (array) $category;
            $queryParametersTypes[ 'category' ] = Connection::PARAM_STR_ARRAY;
        }
        if ($sortBy) {
            $sortOrder = " ORDER BY " . join(', ', $sortBy);
        }

        $sql = '
          SELECT
            up.id, up.country, up.category, p.ip, ps.server_ip as serverIp, up.port,
            up.rotation_time as rotationTime, pr.id as regionId, up.pending_replace as pendingReplace,
            IFNULL(up.time_updated, up.last_rotated) as updateTime,
            IF(up.category != "sneaker", pr.region, 
              case
                  when p.host_loc = "buffalo, ny" then "NY"
                  when p.host_loc = "los angeles, ca" then "LA"
              end ) as region            
          FROM user_ports up
          LEFT JOIN proxies_ipv4 p ON up.proxy_ip = p.id
          LEFT JOIN proxy_server ps ON server_id = ps.id
          LEFT JOIN proxy_regions pr ON pr.id = p.region_id
          WHERE user_type = :userType AND user_id = :userId' . $andWhere . $sortOrder;
        $ports = $this->getConn()->fetchAll($sql, $queryParameters, $queryParametersTypes);
        $ports = array_map(function (array $row) {
            $row[ 'category' ] = Port::toNewCategory($row[ 'category' ]);

            return $row;
        }, $ports);

        return [
            'list' => $ports
        ];
    }

    public function getAllocationAction($userId, $country = [], $category = [])
    {
        $user = $this->getUser($userId);

        $country = (array) $country;
        $category = array_map(function ($category) { return Port::toOldCategory($category); }, (array) $category);

        $queryParameters = [
            'userType' => Port::TYPE_CLIENT,
            'userId'   => $user[ 'id' ],
            'ipv4' => Port::INTERNET_PROTOCOL
        ];
        $queryParametersTypes = [];

        $andWhere = '';
        if ($country) {
            $andWhere .= " AND up.country IN (:country)";
            $queryParameters[ 'country' ] = $country;
            $queryParametersTypes[ 'country' ] = Connection::PARAM_STR_ARRAY;
        }
        if ($category) {
            $andWhere .= " AND up.category IN (:category)";
            $queryParameters[ 'category' ] = $category;
            $queryParametersTypes[ 'category' ] = Connection::PARAM_STR_ARRAY;
        }

        $data = $this->getConn()->fetchAll("
            SELECT up.country, up.category, up.region_id as regionId, count(*) as count
            FROM user_ports up
            INNER JOIN proxy_user_packages pup ON pup.country = up.country AND pup.category = up.category AND 
              pup.user_id = up.user_id AND pup.ip_v = :ipv4
            WHERE up.user_id = :userId and up.user_type = :userType $andWhere
            GROUP BY up.country, up.category, up.region_id 
            ORDER BY pup.id DESC", $queryParameters, $queryParametersTypes);

        $allocation = [];
        foreach ($data as $row) {
            $row[ 'category' ] = Port::toNewCategory($row[ 'category' ]);

            if (!isset($allocation[ $row[ 'country' ] ][ $row[ 'category' ] ])) {
                $allocation[ $row[ 'country' ] ][ $row[ 'category' ] ] = [
                    'count'   => 0,
                    'regions' => []
                ];
            }

            $allocation[ $row[ 'country' ] ][ $row[ 'category' ] ][ 'count' ] += $row[ 'count' ];
            $allocation[ $row[ 'country' ] ][ $row[ 'category' ] ][ 'regions' ][ (int) $row[ 'regionId' ] ] = $row[ 'count' ];
        }

        return [
            'list' => $allocation
        ];
    }

    public function setAllocationAction($userId, $country, $category, array $allocation)
    {
        $user = $this->getUser($userId);
        $category = Port::toOldCategory($category);

        $queryParameters = [
            'userType' => Port::TYPE_CLIENT,
            'userId'   => $user[ 'id' ],
            'country'  => $country,
            'category' => $category
        ];

        // Check ports
        $allocated = $this->getConn()->executeQuery(
            'SELECT region_id, count(*) as count FROM user_ports 
            WHERE user_type = :userType AND user_id = :userId AND country = :country AND category = :category
            GROUP BY region_id',
            $queryParameters)->fetchAll();
        $allocated = Arr::pluck($allocated, 'count', 'region_id');
        $this->assertOrException($allocated, "No \"$country-$category\" ports are exists");
        $portsExists = array_sum(array_values($allocated));
        $portsPassed = array_sum(array_values($allocation));
        $this->assertOrException($portsPassed == $portsExists,
            "Passed $portsPassed ports while $portsExists are expected"
        );

        // Check regions
        $regionsPassed = array_keys($allocation);
        $regionsExists = $this->getConn()->executeQuery(
            'SELECT id FROM proxy_regions WHERE id IN(?)',
            [$regionsPassed], [Connection::PARAM_INT_ARRAY]
        )->fetchAll();
        $regionsExists = Arr::pluck($regionsExists, 'id');
        $this->assertOrException(count($regionsPassed) == count($regionsExists),
            "Regions which are do not exist: " . join(', ', array_diff($regionsExists, $regionsPassed))
        );

        $needAllocate = [];
        // Prepare allocation
        // 1st phase: update counts
        foreach ($allocation as $regionId => $count) {
            if (!$count) {
                continue;
            }

            $this->assertOrException($count > 0, "All the values should be > 0");
            // New region selected
            if (empty($allocated[$regionId])) {
                $needAllocate[$regionId] = $count;
            }
            else {
                // The same quantity, isn't updated
                if ($count == $allocated[$regionId]) {
                    continue;
                }

                $needAllocate[$regionId] = $count - $allocated[$regionId];
            }
        }
        // 2nd phase: remove old regions
        foreach ($allocated as $regionId => $count) {
            if (!$count) {
                continue;
            }

            // Already updated, no need to process
            if (!empty($allocation[$regionId])) {
                continue;
            }

            $needAllocate[$regionId] = -$count;
        }

        // Set new regions allocation,
        // 1st phase: decrease count (make'em free)
        foreach ($needAllocate as $regionId => $count) {
            if ($count > 0) {
                continue;
            }

            $this->getConn()->executeQuery(
                "UPDATE user_ports SET region_id = 0
                WHERE id IN (
                  SELECT * FROM (
                      SELECT id FROM user_ports
                      WHERE user_id = :userId AND user_type = :userType AND country = :country AND category = :category
                      AND region_id = :regionId
                      ORDER BY remove_order ASC, id DESC
                      LIMIT :limit
                  ) t
                )",
                array_merge($queryParameters, ['limit' => abs((int) $count), 'regionId' => $regionId]),
                ['limit' => \PDO::PARAM_INT]
            );
        }
        // 2nd phase: increase count
        foreach ($needAllocate as $regionId => $count) {
            if ($count < 0) {
                continue;
            }

            $this->getConn()->executeQuery(
                "UPDATE user_ports SET region_id = :regionId
                WHERE user_id = :userId AND user_type = :userType AND country = :country AND category = :category
                AND region_id = 0
                LIMIT :limit",
                array_merge($queryParameters, ['limit' => (int) $count, 'regionId' => $regionId]),
                ['limit' => \PDO::PARAM_INT]
            );
        }

        return array_merge($this->getAllocationAction($userId, $country, $category), ['diff' => $needAllocate, 'before' => $allocated]);
    }

    public function setRotationTimeAction($userId, $id, $time)
    {
        $user = $this->getUser($userId);

        $this->assertOrException($time >= 10, 'Time cannot be less than 10');
        $affected = $this->getConn()->update('user_ports', ['rotation_time' => $time], [
            'user_id'   => $user[ 'id' ],
            'user_type' => Port::TYPE_CLIENT,
            'id'        => $id,
        ]);
        $this->assertOrException($affected > 0, 'No port has been found');

        return [
            'status' => 'ok',
            'time'   => $time
        ];
    }

    public function getAvailableReplacementsAction($userId)
    {
        $user = $this->getUser($userId);

        $replacements = $this->getConn()->fetchAll(
            "SELECT pup.country, pup.category, IF(pup.ports > 0, pup.ports, pp.ports) - pup.replacements as available
            FROM proxy_user_packages pup
            LEFT JOIN proxy_packages pp ON pp.id = pup.package_id
            WHERE pup.user_id = :userId and pup.category IN (:categories)",
            [
                'userId'     => $user[ 'id' ],
                'categories' => [
                    Port::toOldCategory(Port::CATEGORY_DEDICATED),
                    Port::CATEGORY_SEMI_DEDICATED,
                    Port::CATEGORY_SNEAKER
                ]
            ],
            ['categories' => Connection::PARAM_STR_ARRAY]);

        $replacements = array_map(function (array $row) {
            $row[ 'category' ] = Port::toNewCategory($row[ 'category' ]);
            if ($row[ 'available' ] < 0) {
                $row[ 'available' ] = 0;
            }

            return $row;
        }, $replacements);

        return [
            'list' => $replacements
        ];
    }

    public function setPendingReplaceAction($userId, $ip)
    {
        $user = $this->getUser($userId);

        // Check ip
        $data = $this->getConn()->executeQuery(
            "SELECT up.id, up.country, up.category 
            FROM user_ports up
            INNER JOIN proxies_ipv4 p ON p.id = up.proxy_ip
            WHERE p.ip = :ip AND up.user_id = :userId AND up.user_type = :userType",
            ['userId' => $user[ 'id' ], 'userType' => Port::TYPE_CLIENT, 'ip' => $ip]
        )->fetch();
        $this->assertOrException($data, "IP \"$ip\" is not exist or not belongs to user");

        // Check available replacements
        $available = 0;
        foreach ($this->getAvailableReplacementsAction($userId)[ 'list' ] as $row) {
            if ($row[ 'country' ] == $data[ 'country' ] and $row[ 'category' ] == Port::toNewCategory($data[ 'category' ])
                and $row[ 'available' ] > 0
            ) {
                $available = $row[ 'available' ];
                break;
            }
        }
        $this->assertOrException($available > 0, "You are out of replacements for this package");

        $stmt = $this->getConn()->executeQuery(
            'UPDATE user_ports SET pending_replace = 1 WHERE id = ? and user_id = ? AND user_type = ?',
            [$data[ 'id' ], $user[ 'id' ], Port::TYPE_CLIENT]
        );
        $this->assertOrException($stmt->rowCount(), "IP \"$ip\" has not been replaced due unknown error");

        $this->getConn()->executeQuery(
            'UPDATE proxy_user_packages SET replacements = replacements + 1 
            WHERE user_id = ? AND country = ? AND category = ?',
            [$user[ 'id' ], $data[ 'country' ], $data[ 'category' ]]
        );

        return [
            'status'    => 'ok',
            'count'     => 1,
            'ip'        => $ip,
            'available' => $available - 1
        ];
    }

    public function setPendingReplaceMultipleAction($userId, array $ip)
    {
        $user = $this->getUser($userId);

        foreach ($ip as $value) {
            $this->assertOrException(filter_var($value, FILTER_VALIDATE_IP), "\"$value\" is not a valid IP");
        }

        // Check ip
        $ip = $this->getConn()->executeQuery(
            "SELECT up.id, up.country, up.category, p.ip 
            FROM user_ports up
            INNER JOIN proxies_ipv4 p ON p.id = up.proxy_ip
            WHERE p.ip IN (:ip) AND up.user_id = :userId AND up.user_type = :userType",
            ['userId' => $user[ 'id' ], 'userType' => Port::TYPE_CLIENT, 'ip' => $ip],
            ['ip' => Connection::PARAM_STR_ARRAY]
        )->fetchAll();
        $this->assertOrException($ip, "None of these IPs belongs to user");

        // Check available replacements
        $stmt = $this->getConn()->executeQuery(
            "SELECT replacing.country, replacing.category
            FROM (
                SELECT country, category, count(*) as count
                FROM user_ports
                WHERE user_id = :userId AND user_type = :userType
                AND proxy_ip IN (SELECT id FROM proxies_ipv4 WHERE ip IN (:ips))
                GROUP BY country, category
            ) replacing
            LEFT JOIN (
                SELECT pup.replacements, pup.country, pup.category
                FROM proxy_user_packages pup
                WHERE pup.user_id = :userId
            ) replacements ON replacements.country = replacing.country and replacements.category = replacing.category
            LEFT JOIN (
                SELECT country, category, count(*) as ports
                FROM user_ports
                WHERE user_id = :userId AND user_type = :userType
                GROUP BY country, category
            ) ports ON ports.country = replacing.country and ports.category = replacing.category
            WHERE (ports - replacements) < count",
            ['userId' => $user[ 'id' ], 'userType' => Port::TYPE_CLIENT, 'ips' => Arr::pluck($ip, 'ip')],
            ['ips' => Connection::PARAM_INT_ARRAY]
        );
        $this->assertFalseOrException($stmt->rowCount(),
            'You are trying to replace too many IPs. Delete some IPs from your request and try again.');

        $stmt = $this->getConn()->executeQuery(
            'UPDATE user_ports SET pending_replace = 1 WHERE id IN(:id) and user_id = :userId AND user_type = :userType',
            ['id' => Arr::pluck($ip, 'id'), 'userId' => $user[ 'id' ], 'userType' => Port::TYPE_CLIENT],
            ['id' => Connection::PARAM_INT_ARRAY]
        );
        $this->assertOrException($stmt->rowCount(), "None of these IPs have been replaced");

        $totalCount = 0;
        $replacedByPackage = [];
        foreach ($ip as $row) {
            if (!isset($replacedByPackage[ $row[ 'country' ] ][ $row[ 'category' ] ])) {
                $replacedByPackage[ $row[ 'country' ] ][ $row[ 'category' ] ] = 0;
            }
            $replacedByPackage[ $row[ 'country' ] ][ $row[ 'category' ] ]++;
            $totalCount++;
        }
        foreach ($replacedByPackage as $country => $categories) {
            foreach ($categories as $category => $replaced) {
                $this->getConn()->executeQuery(
                    'UPDATE proxy_user_packages SET replacements = replacements + :replaced
                    WHERE user_id = :userId AND country = :country AND category = :category',
                    ['userId'   => $user[ 'id' ],
                     'country'  => $country,
                     'category' => $category,
                     'replaced' => (int) $replaced
                    ],
                    ['replaced' => \PDO::PARAM_INT]
                );
            }
        }

        return [
            'status'    => 'ok',
            'count'     => $totalCount,
            'ip'        => Arr::pluck($ip, 'ip'),
            'available' => $this->getAvailableReplacementsAction($userId)[ 'list' ]
        ];
    }

    public function setPreservedPortsAction($userId, array $ip) {
        $user = $this->getUser($userId);

        foreach ($ip as $value) {
            $this->assertOrException(filter_var($value, FILTER_VALIDATE_IP), "\"$value\" is not a valid IP");
        }

        // Check ip
        $ip = $this->getConn()->executeQuery(
            "SELECT up.id, up.country, up.category, p.ip, p.id 
            FROM user_ports up
            INNER JOIN proxies_ipv4 p ON p.id = up.proxy_ip
            WHERE p.ip IN (:ip) AND up.user_id = :userId AND up.user_type = :userType",
            ['userId' => $user[ 'id' ], 'userType' => Port::TYPE_CLIENT, 'ip' => $ip],
            ['ip' => Connection::PARAM_STR_ARRAY]
        )->fetchAll();
        $this->assertOrException($ip, "None of these IPs belongs to user");

        $this->getConn()->executeQuery(
            "UPDATE user_ports
            SET remove_order = IF(user_ports.proxy_ip IN (:ip), 1, 0)
            WHERE user_ports.country = :country and user_ports.category = :category and user_ports.user_id = :userId",
            ['country' => $ip[0][ 'country' ], 'category' => $ip[0][ 'category' ], 'ip' => array_column($ip, 'id'), 'userId' => $user[ 'id' ]],
            ['ip' => Connection::PARAM_STR_ARRAY]
        )->execute();

        return ['status' => 'ok'];
    }
}
