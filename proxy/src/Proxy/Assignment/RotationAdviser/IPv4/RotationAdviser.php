<?php

namespace Proxy\Assignment\RotationAdviser\IPv4;

use Axelarge\ArrayTools\Arr;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Proxy\Assignment\Port\CommonPackageContext;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\Assignment\RotationAdviser\AbstractRotationAdviser;
use Proxy\Assignment\RotationAdviser\IPv4\Specific\RotatingRotationAdviser;
use Proxy\Assignment\RotationAdviser\IPv4\Specific\SpecialCustomerAdviser;
use Proxy\Assignment\RotationAdviser\KushangRotationAdviser;

class RotationAdviser extends AbstractRotationAdviser
{
    protected $config = [
        'sneakerDate' => '2016-12-30',
        'load' => [
            'default' => 500,
            'sneaker' => 10
        ],
        'rules' => [
            'dedicated' => [
                // These users will receive other users proxies
                'onTopUsers' => [
                    'enabled' => true,
                    'users' => [10335, 10761, 10762, 10763, 10765]
                ],
                'doubleUpUsers' => [
                    'enabled' => true,
                    'users' => [6822]
                ],
                'doubleSellLocationsBefore' => [
                    'count' => 2,
                    // => ['us' => [2], 'br' => ['*']]
                    'locations' => ['us' => [], 'de' => [], 'br' => []]
                ],
                'exclude' => [
                    'hostLocations' => ['nexeon-chicago-1', 'los angeles 2'],
                    'servers' => ['%disabled%']
                ],
                'doubleSellPackages' => [
                    'enabled' => false,
                    'perPackage' => 2,
                    'maxPackagePorts' => 100,
                    'newCustomersUpToPercentage' => 25
                ]
            ],
            'sneaker' => [
                // These users will receive other users proxies
                'onTopUsers' => [
                    'enabled' => true,
                    'users' => [10335, 10761, 10762, 10763, 10765]
                ],
                'locations' => [
                    'NY' => ['location' => 'buffalo, ny', 'hostLocations' => ['buffalo, ny'], 'enabled' => true],
                    'LA' => [
                        'location'      => 'los angeles, ca', // deprecated
                        'hostLocations' => ['los angeles, ca'],
                        'enabled'       => true,
                        'source'        => ['budgetvm%'],
                        'perClassC'     => 50
                    ],
                ],
            ],
            'semi' => [
                'groupQuantity' => 5,
                'groupQuantityCountries' => [
                    'br' => 6
                ],
                'onTopUsers' => [
                    'enabled' => true,
                    'users' => [10335, 10761, 10762, 10763, 10765]
                ],
                'exclude' => [
                    'hostLocations' => ['nexeon-chicago-1', 'los angeles 2']
                ]
            ],
            'rotating' => [
                'rotateMultiple' => 20,
                'onTopUsers' => [
                    'enabled' => true,
                    'users' => [10335, 10761, 10762, 10763, 10765]
                ],
                'perIp' => 1,
                'perIpCountries' => [
                    'br' => 2
                ]
            ]
        ],
        'fakeLocations' => ['Los Angeles 2'],
        'sneaker' => [
            'doubleSellStaticAtFirst' => true,
            'doubleSellUsers' => [
                'enabled' => true,
                'users' => [3133, 4359, 4072, 5915, 5173, 7893]
            ],
            'doubleSellStatic' => [
                'enabled' => false,
                'maxPerIp' => 2
            ],
            'doubleSellBlocks' => [
                'enabled' => false
            ]
        ],
        'rulesPerUser' => []
    ];
    protected $externalAdvisersMap = [
        'rotating' => RotatingRotationAdviser::class,
        'special' => SpecialCustomerAdviser::class,
    ];
    protected $externalAdvisers = [];

    protected function getNameClassConfig()
    {
        return 'assign';
    }

    /**
     * @param Port $port
     * @return bool|int Proxy id or false otherwise
     */
    public function findRandomDedicatedProxy(Port $port)
    {
        $queryArgs = [
            'country'  => $port->getCountry(),
            'userType' => $port->getUserType(),
            'userId'   => $port->getUserId(),
            'regionId' => $port->getRegionId(),
            'limit'    => $this->getFromConfig('load.default'),
            'categoryDedi' => Port::toOldCategory(Port::CATEGORY_DEDICATED),
            'categorySneaker' => Port::CATEGORY_SNEAKER,
            'countryUS' => Port::COUNTRY_US,
            'ipV4' => Port::INTERNET_PROTOCOL,
        ];
        $queryTypes = ['limit' => \PDO::PARAM_INT];

        $whereCountry = 'and pr.country = :country';
        $whereLocation = '';
        $whereDate = '';
        $whereNotHostLocation = '';

        if (Port::COUNTRY_US == $port->getCountry()) {
            if ($port->getRegionId() and 1 != $port->getRegionId()) {
                $whereLocation = 'and pr.region_id = :regionId';
            }

            $whereDate = "and date_added < :sneakerDate";
            $queryArgs['sneakerDate'] = $this->getFromConfig('sneakerDate');
        }
        elseif (Port::COUNTRY_INTERNATIONAL == $port->getCountry()) {
            $whereCountry = 'and pr.country != :countryUS';
            $queryArgs['countryUS'] = Port::COUNTRY_US;
            if ($port->getRegionId() and !in_array($port->getRegionId(), [1, 32])) {
                $whereLocation = 'and pr.region_id = :regionId';
            }
        }

        if ($locations = $this->getRule('dedicated.exclude.hostLocations', $port)) {
            $whereNotHostLocation = 'AND pr.host_loc NOT IN(:notHostLocations)';
            $queryArgs['notHostLocations'] = $locations;
            $queryTypes['notHostLocations'] = Connection::PARAM_STR_ARRAY;
        }

        $whereNotHostServer = '';
        if ($excludeServers = $this->getRule('dedicated.exclude.servers', $port)) {
            $whereNotHostServer = [];
            foreach ($excludeServers as $i => $serverName) {
                $whereNotHostServer[] = "ps.name NOT LIKE :notServer$i";
                $queryArgs["notServer$i"] = $serverName;
            }
            $whereNotHostServer = $whereNotHostServer ? ('AND (' . join(' AND ', $whereNotHostServer) . ')') : '';
        }

        // Double up customers proxies
        if ($this->getRule('dedicated.onTopUsers.enabled', $port) and
            in_array($port->getUserId(), $this->getRule('dedicated.onTopUsers.users', $port))) {
            $sql = "SELECT id
            FROM (
                SELECT substring_index( pr.ip, '.', 1 ) as a_class,
                substring_index( pr.ip, '.', 2 ) as b_class,
                substring_index( pr.ip, '.', 3 ) as c_class,
                count(*) as system_count,
                pr.id
                FROM proxies_ipv4 pr
                INNER JOIN proxy_source ps ON ps.id = pr.source_id
                WHERE dead = 0 and static = 1 and active = 1 $whereCountry $whereLocation $whereDate
                $whereNotHostLocation AND host_loc != 'los angeles 2'
                $whereNotHostServer
                AND pr.id IN (SELECT `proxy_ip` FROM `user_ports` WHERE category = :categoryDedi)
                and pr.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id IN (:users))
                and pr.id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_type = :userType and user_id IN (:users))
                and pr.id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                AND pr.id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                GROUP BY a_class, b_class, c_class
                ORDER BY last_used DESC
            ) as pr
            LEFT JOIN (
                SELECT
                substring_index( ip, '.', 1 ) as a_class,
                substring_index( ip, '.', 2 ) as b_class,
                substring_index( ip, '.', 3 ) as c_class,
                count(*) as user_count
                FROM  `proxies_ipv4` pr
                LEFT JOIN user_ports up on up.proxy_ip = pr.id
                WHERE up.user_type = :userType and up.user_id = :userId
                GROUP BY a_class, b_class, c_class
            ) as usr ON pr.a_class = usr.a_class and pr.b_class = usr.b_class and pr.c_class = usr.c_class
            ORDER BY (user_count / system_count)
            LIMIT :limit";

            $result = $this->getCachedQueryResult(
                $sql,
                array_merge($queryArgs, ['users' => $this->getRule('dedicated.onTopUsers.users', $port)]),
                array_merge($queryTypes, ['users' => Connection::PARAM_INT_ARRAY]),
                function(Statement $stmt) {
                    return $stmt->fetchColumn();
            });

            if ($result) {
                $this->log("Found dedi id $result by \"on top users\" logic case");

                return $result;
            }
        }

        // Double up customers proxies
        if ($this->getRule('dedicated.doubleUpUsers.enabled', $port) and
            !in_array($port->getUserId(), $this->getRule('dedicated.doubleUpUsers.users', $port))) {
            $sql = "SELECT id
            FROM (
                SELECT substring_index( pr.ip, '.', 1 ) as a_class,
                substring_index( pr.ip, '.', 2 ) as b_class,
                substring_index( pr.ip, '.', 3 ) as c_class,
                count(*) as system_count,
                pr.id
                FROM proxies_ipv4 pr
                INNER JOIN proxy_source ps ON ps.id = pr.source_id
                WHERE dead = 0 and static = 1 and active = 1 $whereCountry $whereLocation $whereDate
                $whereNotHostLocation
                $whereNotHostServer
                and pr.id IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id IN (:users))
                and pr.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id NOT IN (:users))
                and pr.id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_type = :userType and user_id NOT IN (:users))
                and pr.id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                AND pr.id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                GROUP BY a_class, b_class, c_class
                ORDER BY last_used DESC
            ) as pr
            LEFT JOIN (
                SELECT
                substring_index( ip, '.', 1 ) as a_class,
                substring_index( ip, '.', 2 ) as b_class,
                substring_index( ip, '.', 3 ) as c_class,
                count(*) as user_count
                FROM  `proxies_ipv4` pr
                LEFT JOIN user_ports up on up.proxy_ip = pr.id
                WHERE up.user_type = :userType and up.user_id = :userId
                GROUP BY a_class, b_class, c_class
            ) as usr ON pr.a_class = usr.a_class and pr.b_class = usr.b_class and pr.c_class = usr.c_class
            ORDER BY (user_count / system_count)
            LIMIT :limit";

            $result = $this->getCachedQueryResult(
                $sql,
                array_merge($queryArgs, ['users' => $this->getRule('dedicated.onTopUsers.users', $port)]),
                array_merge($queryTypes, ['users' => Connection::PARAM_INT_ARRAY]),
                function(Statement $stmt) {
                    return $stmt->fetchColumn();
                });

            if ($result) {
                $this->log("Found dedi id $result by \"double up users\" logic case");

                return $result;
            }
        }

        // Double sell dedicated proxies if defined
        if ($locations = $this->getRule('dedicated.doubleSellLocationsBefore.locations', $port)) {
            if (!empty($locations[ strtolower($port->getCountry()) ]) and
                (
                    in_array($port->getRegionId(), $locations[ strtolower($port->getCountry()) ]) or
                    in_array('*', $locations[ strtolower($port->getCountry()) ])
                )
            ) {
                $sql = "SELECT id, count
                FROM (
                    SELECT substring_index( ip, '.', 1 ) as a_class,
                    substring_index( ip, '.', 2 ) as b_class,
                    substring_index( ip, '.', 3 ) as c_class,
                    count(*) as system_count,
                    max(id) as id,
                    count
                    FROM (
                        SELECT pr.id, pr.ip, pr.last_used, COUNT(up.id) as count
                        FROM  proxies_ipv4 pr
                        INNER JOIN proxy_source ps ON ps.id = pr.source_id
                        LEFT JOIN user_ports up on up.proxy_ip = pr.id
                        WHERE pr.active = 1 AND pr.dead = 0 AND pr.static = 1 $whereCountry $whereLocation $whereDate
                        $whereNotHostLocation
                        $whereNotHostServer
                        and (up.country = :country and up.category = :categoryDedi)                   
                        and pr.id IN (SELECT proxy_ip FROM user_ports WHERE category = :categoryDedi)
                        and pr.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                        and pr.id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_type = :userType and user_id = :userId)
                        and pr.id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                        AND pr.id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                        GROUP BY pr.id
                        HAVING COUNT(up.id) <= :doubleCount
                        ORDER BY count DESC
                    ) as pr2
                    GROUP BY a_class, b_class, c_class
                    ORDER BY last_used DESC
                ) as pr
                LEFT JOIN (
                    SELECT
                    substring_index( ip, '.', 1 ) as a_class,
                    substring_index( ip, '.', 2 ) as b_class,
                    substring_index( ip, '.', 3 ) as c_class,
                    count(*) as user_count
                    FROM  `proxies_ipv4` pr
                    LEFT JOIN user_ports up on up.proxy_ip = pr.id
                    WHERE up.user_type = :userType and up.user_id = :userId
                    GROUP BY a_class, b_class, c_class
                ) as usr ON pr.a_class = usr.a_class and pr.b_class = usr.b_class and pr.c_class = usr.c_class
                ORDER BY count DESC, (user_count / system_count)
                LIMIT :limit";

                $result = $this->getCachedQueryResult($sql, array_merge($queryArgs, [
                   'doubleCount' => max($this->getRule('dedicated.doubleSellLocationsBefore.count', $port) - 1, 0)
                ]), $queryTypes, function (Statement $stmt) {
                    return $stmt->fetchColumn();
                });

                if ($result) {
                    $this->log("Found dedi id $result by \"double sell before\" logic case");

                    return $result;
                }
            }
        }

        $sql = "SELECT id
            FROM (
                SELECT substring_index( pr.ip, '.', 1 ) as a_class,
                substring_index( pr.ip, '.', 2 ) as b_class,
                substring_index( pr.ip, '.', 3 ) as c_class,
                count(*) as system_count,
                pr.id
                FROM proxies_ipv4 pr
                INNER JOIN proxy_source ps ON ps.id = pr.source_id
                WHERE dead = 0 and static = 1 and active = 1 $whereCountry $whereLocation $whereDate
                $whereNotHostLocation
                $whereNotHostServer
                AND pr.id NOT IN (SELECT `proxy_ip` FROM `user_ports`)
                and pr.id NOT IN (SELECT proxy_id FROM user_ports_frozen)
                and pr.id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                AND pr.id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                GROUP BY a_class, b_class, c_class
                ORDER BY last_used DESC
            ) as pr
            LEFT JOIN (
                SELECT
                substring_index( ip, '.', 1 ) as a_class,
                substring_index( ip, '.', 2 ) as b_class,
                substring_index( ip, '.', 3 ) as c_class,
                count(*) as user_count
                FROM  `proxies_ipv4` pr
                LEFT JOIN user_ports up on up.proxy_ip = pr.id
                WHERE up.user_type = :userType and up.user_id = :userId
                GROUP BY a_class, b_class, c_class
            ) as usr ON pr.a_class = usr.a_class and pr.b_class = usr.b_class and pr.c_class = usr.c_class
            ORDER BY (user_count / system_count)
            LIMIT :limit";

        $result = $this->getCachedQueryResult($sql, $queryArgs, $queryTypes, function(Statement $stmt) {
            return $stmt->fetchColumn();
        });

        if ($result) {
            $this->log("Found dedi id $result by \"common\" logic case");

            return $result;
        }

        // Double sell sneaker proxies as last resort is only allowed for US based proxies
        if (Port::COUNTRY_US == $port->getCountry()) {
            $sql = "SELECT id
            FROM (
                SELECT substring_index( pr.ip, '.', 1 ) as a_class,
                substring_index( pr.ip, '.', 2 ) as b_class,
                substring_index( pr.ip, '.', 3 ) as c_class,
                count(*) as system_count,
                pr.id
                FROM proxies_ipv4 pr
                INNER JOIN proxy_source ps ON ps.id = pr.source_id
                WHERE dead = 0 and static = 1 and active = 1 $whereCountry $whereLocation $whereDate
                $whereNotHostLocation
                $whereNotHostServer
                AND pr.id IN (SELECT `proxy_ip` FROM `user_ports` WHERE country = :countryUS AND category = :categorySneaker)
                AND pr.id NOT IN (SELECT `proxy_ip` FROM `user_ports` WHERE country = :countryUS AND category != :categorySneaker)
                and pr.id NOT IN (SELECT proxy_id FROM user_ports_frozen)
                and pr.id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                AND pr.id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                GROUP BY a_class, b_class, c_class
                ORDER BY last_used DESC
            ) as pr
            LEFT JOIN (
                SELECT
                substring_index( ip, '.', 1 ) as a_class,
                substring_index( ip, '.', 2 ) as b_class,
                substring_index( ip, '.', 3 ) as c_class,
                count(*) as user_count
                FROM  `proxies_ipv4` pr
                LEFT JOIN user_ports up on up.proxy_ip = pr.id
                WHERE up.user_type = :userType and up.user_id = :userId
                GROUP BY a_class, b_class, c_class
            ) as usr ON pr.a_class = usr.a_class and pr.b_class = usr.b_class and pr.c_class = usr.c_class
            ORDER BY (user_count / system_count)
            LIMIT :limit";

            $result = $this->getCachedQueryResult($sql, $queryArgs, array_merge($queryTypes, [
                'limit'    => \PDO::PARAM_INT,
                'maxPerId' => \PDO::PARAM_INT
            ]), function (Statement $stmt) {
                return $stmt->fetchColumn();
            });

            if ($result) {
                $this->log("Found dedi id $result by \"double sell sneaker last resort\" logic case");

                return $result;
            }
        }

        if ($this->getRule('dedicated.doubleSellPackages.enabled', $port)) {
            $allowed = true;
            if (CommonPackageContext::NEED_NEW == $port->getContext()->getNeed()) {
                $assignment = $this->conn->fetchAssoc(
                    "SELECT COUNT(IF(proxy_ip > 0, 1, null)) as assigned, COUNT(id) as total 
                    FROM user_ports 
                    WHERE country = :country AND category = :categoryDedi AND user_id = :userId",
                    $queryArgs,
                    $queryTypes
                );
                if ($assignment) {
                    if (round($assignment['assigned'] / $assignment['total'] * 100) >
                        $this->getRule('dedicated.doubleSellPackages.newCustomersUpToPercentage', $port)) {
                        $allowed = false;
                    }
                }
            }

            if ($allowed) {
                $sql = "SELECT id
                FROM (
                    SELECT substring_index( pr.ip, '.', 1 ) as a_class,
                    substring_index( pr.ip, '.', 2 ) as b_class,
                    substring_index( pr.ip, '.', 3 ) as c_class,
                    count(*) as system_count,
                    pr.id
                    FROM proxies_ipv4 pr
                    INNER JOIN proxy_source ps ON ps.id = pr.source_id
                    WHERE dead = 0 and static = 1 and active = 1 $whereCountry $whereLocation $whereDate
                    $whereNotHostLocation
                    $whereNotHostServer
                    AND pr.id IN (
                        SELECT proxy_ip
                        FROM (
                            SELECT pup.user_id, pup.id as package_id, up.proxy_ip, 
                              count(up.id) as ports, count(upd.id) as ports_doubled
                            FROM proxy_user_packages pup
                            INNER JOIN user_ports up ON up.country = pup.country AND up.category = pup.category 
                              AND up.user_id = pup.user_id AND up.user_type = :userType
                            LEFT JOIN user_ports upd ON upd.country = pup.country AND upd.category = pup.category 
                              AND upd.user_id != pup.user_id AND upd.proxy_ip = up.proxy_ip
                            WHERE pup.country = :country AND pup.category = :categoryDedi AND up.proxy_ip > 0 
                              AND pup.ports <= :maxPackagePorts
                              AND up.proxy_ip IN (
                                SELECT proxy_ip 
                                FROM user_ports 
                                WHERE proxy_ip > 0 AND category = :categoryDedi 
                                GROUP BY proxy_ip 
                                HAVING COUNT(*) <= 1
                              )
                            GROUP BY pup.id
                            HAVING ports_doubled <= :maxPerPort
                            ORDER BY ports_doubled ASC
                        ) t
                        GROUP BY user_id
                    )
                    AND pr.id NOT IN (SELECT `proxy_ip` FROM `user_ports` WHERE country = :country AND category != :categoryDedi)
                    and pr.id NOT IN (SELECT proxy_id FROM user_ports_frozen)
                    and pr.id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                    AND pr.id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                    GROUP BY a_class, b_class, c_class
                    ORDER BY last_used DESC
                ) as pr
                LEFT JOIN (
                    SELECT
                    substring_index( ip, '.', 1 ) as a_class,
                    substring_index( ip, '.', 2 ) as b_class,
                    substring_index( ip, '.', 3 ) as c_class,
                    count(*) as user_count
                    FROM  `proxies_ipv4` pr
                    LEFT JOIN user_ports up on up.proxy_ip = pr.id
                    WHERE up.user_type = :userType and up.user_id = :userId
                    GROUP BY a_class, b_class, c_class
                ) as usr ON pr.a_class = usr.a_class and pr.b_class = usr.b_class and pr.c_class = usr.c_class
                ORDER BY (user_count / system_count)
                LIMIT :limit";

                $result = $this->getCachedQueryResultColumn($sql, array_merge($queryArgs, [
                    'maxPackagePorts' => $this->getRule('dedicated.doubleSellPackages.maxPackagePorts', $port),
                    'maxPerPort' => $this->getRule('dedicated.doubleSellPackages.perPackage', $port) - 1,
                    // The last customer will be double sold N times - a flaw of such method
                    'limit' => 5
                ]), array_merge($queryTypes, [
                    'maxPackagePorts' => \PDO::PARAM_INT,
                    'maxPerPort' => \PDO::PARAM_INT
                ]));

                if ($result) {
                    $this->log("Found dedi id $result by \"double sell packages last resort\" logic case",
                        array_merge([], !empty($assignment) ? [
                            'assignment' => $assignment,
                            'threshold' => $this->getRule('dedicated.doubleSellPackages.newCustomersUpToPercentage', $port)
                        ] : [])
                    );

                    return $result;
                }
            }
        }

        return false;
    }

    public function findRandomSneakerProxy(Port $port)
    {
        if ($port->getSneakerLocation() and
            !empty($this->getFromConfig('rules.sneaker.locations')[$port->getSneakerLocation()]) and
            $this->getFromConfig('rules.sneaker.locations.' . $port->getSneakerLocation() . '.enabled')) {
            $locationData = $this->getFromConfig('rules.sneaker.locations.' . $port->getSneakerLocation());
            $hostLocations = array_unique(array_merge(
                [$locationData['location']],
                !empty($locationData['hostLocations']) ? $locationData['hostLocations'] : []
            ));
        }
        elseif (Port::COUNTRY_US != $port->getCountry()) {
            $hostLocations = [];
        }
        else {
            return false;
        }

        $queryArgs = [
            'country'       => $port->getCountry(),
            'userType'      => $port->getUserType(),
            'userId'        => $port->getUserId(),
            'hostLocations' => $hostLocations,
            'limit'         => $this->getFromConfig('load.sneaker'),

            'categorySneaker' => Port::CATEGORY_SNEAKER,
            'categorySupreme' => Port::CATEGORY_SUPREME,
            'categoryDedi'    => Port::toOldCategory(Port::CATEGORY_DEDICATED),
            'categorySemi'    => Port::CATEGORY_SEMI_DEDICATED,
            'categoryBlock'   => Port::CATEGORY_BLOCK,
            'countryUS'       => Port::COUNTRY_US,
        ];
        $queryTypes = ['limit' => \PDO::PARAM_INT, 'hostLocations' => Connection::PARAM_STR_ARRAY];
        $joinSubnet = "LEFT JOIN (
            SELECT substring_index( ip, '.', 3 ) as c_class,
            count(*) as user_count
            FROM proxies_ipv4 pr
            LEFT JOIN user_ports up on up.proxy_ip = pr.id      
            WHERE up.country = :country AND up.category = :categorySneaker
            GROUP BY c_class
        )";

        $whereSource = '';
        if (!empty($locationData['source']) and Port::COUNTRY_US == $port->getCountry()) {
            $whereSource = ' AND (' . join(' OR ', array_map(function($source) {
                return "pr.source LIKE '$source'";
            }, $locationData['source'])) . ')';
        }
        $whereLocation = '';
        if ($hostLocations) {
            $whereLocation = 'and host_loc IN (:hostLocations)';
        }

        $whereOnSubnetC = '1 = 1';
        if (!empty($locationData['perClassC']) and $locationData['perClassC'] > 1) {
            $whereOnSubnetC = 'IFNULL(usr.user_count, 0) < :perClassC';
            $queryArgs['perClassC'] = $locationData['perClassC'];
        }

        // Double up customers proxies
        if ($this->getFromConfig('rules.sneaker.onTopUsers.enabled') and
            in_array($port->getUserId(), $this->getFromConfig('rules.sneaker.onTopUsers.users'))) {
            $sql = "SELECT id
            FROM (
                SELECT id,
                count(*) as system_count,
                substring_index( ip, '.', 3 ) as c_class
                FROM proxies_ipv4 pr
                WHERE dead = 0 and static = 1 and active = 1 and country = :country 
                $whereLocation $whereSource
                AND id IN (SELECT proxy_ip FROM user_ports WHERE category = :categorySneaker)
                and id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                and id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_type = :userType and user_id = :userId)
                and id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                AND id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                GROUP BY c_class
                ORDER BY last_used DESC
            ) as pr
            $joinSubnet as usr ON pr.c_class = usr.c_class
            WHERE $whereOnSubnetC
            ORDER BY (user_count / system_count), system_count DESC
            LIMIT :limit";

            $result = $this->getCachedQueryResultColumn($sql, $queryArgs, $queryTypes);

            if ($result) {
                $this->log("Found sneaker id $result by \"double up\" logic case");

                return $result;
            }
        }

        // Double sell dedi and semi proxies (1 proxy per supreme/sneaker, 2 proxies per dedi+sneaker)
        if ($this->getFromConfig('sneaker.doubleSellStaticAtFirst') and Port::COUNTRY_US == $port->getCountry()) {
            $sql = "SELECT id 
            FROM (
                SELECT pr.id,
                    count(*) as system_count,
                    substring_index( ip, '.', 3 ) as c_class
                FROM `proxies_ipv4` pr
                WHERE
                  dead = 0 and static = 1 and active = 1 and country = :country 
                  $whereLocation $whereSource
                  AND id IN (SELECT proxy_ip FROM user_ports WHERE category IN (:categoryDedi, :categorySemi) and country = :country)
                  AND id NOT IN (SELECT proxy_ip FROM user_ports WHERE category IN (:categorySneaker, :categorySupreme) and country = :country)
                  and id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                  and id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_type = :userType and user_id = :userId)
                  AND id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                  AND id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                GROUP BY c_class
              ) as pr
              $joinSubnet as usr ON pr.c_class = usr.c_class
              WHERE $whereOnSubnetC
              ORDER BY (user_count / system_count), system_count DESC
              LIMIT :limit";

            $result = $this->getCachedQueryResultColumn($sql, $queryArgs, $queryTypes);

            if ($result) {
                $this->log("Found sneaker id $result by \"double sell dedi and semi\" logic case");

                return $result;
            }
        }

        // Double selling some customer proxies
        if ($this->getFromConfig('sneaker.doubleSellUsers.enabled') and $this->getFromConfig('sneaker.doubleSellUsers.users')
            and !in_array($port->getUserId(), $this->getFromConfig('sneaker.doubleSellUsers.users'))) {
            $users = join(',', $this->getFromConfig('sneaker.doubleSellUsers.users'));

            $sql = "SELECT id 
              FROM ( 
                SELECT id,
                  count(*) as system_count,
                  substring_index( ip, '.', 3 ) as c_class
                FROM proxies_ipv4 pr
                WHERE dead = 0 and static = 1 and active = 1 and country = :country 
                $whereLocation $whereSource
                AND id IN (SELECT proxy_ip FROM user_ports WHERE user_id IN($users))            
                AND id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_id NOT IN ($users))
                AND id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_id NOT IN ($users))
                and id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                AND id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                GROUP BY c_class
                ORDER BY rand()
              )
              as pr
              $joinSubnet as usr ON pr.c_class = usr.c_class
              WHERE $whereOnSubnetC
              ORDER BY (user_count / system_count), system_count DESC
              LIMIT :limit";

            $result = $this->getCachedQueryResultColumn($sql, $queryArgs, $queryTypes);

            // Can be double-sold
            if ($result) {
                $this->log("Found sneaker id $result by \"double sell some users\" logic case");

                return $result;
            }
        }

        $sql = "SELECT id
            FROM (
                SELECT id,
                count(*) as system_count,
                substring_index( ip, '.', 3 ) as c_class
                FROM proxies_ipv4 pr
                WHERE dead = 0 and static = 1 and active = 1 and country = :country 
                $whereLocation $whereSource
                AND id NOT IN (SELECT proxy_ip FROM user_ports)
                AND id NOT IN (SELECT proxy_id FROM user_ports_frozen)
                and id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                AND id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                GROUP BY c_class
                ORDER BY last_used DESC
            ) as pr
            $joinSubnet as usr ON pr.c_class = usr.c_class
            WHERE $whereOnSubnetC
            ORDER BY (user_count / system_count), system_count DESC
            LIMIT :limit";

        $result = $this->getCachedQueryResultColumn($sql, $queryArgs, $queryTypes);

        if ($result) {
            $this->log("Found sneaker id $result by \"common\" logic case");

            return $result;
        }

        if ($this->getFromConfig('sneaker.doubleSellBlocks.enabled')) {
            $sql = "SELECT id
                FROM (
                SELECT proxy_ip as id,
                    count(*) as system_count,
                    substring_index(ip, '.', 3) as c_class
                    FROM user_ports up
                    INNER JOIN proxies_ipv4 p ON p.id = proxy_ip
                    WHERE category IN (:categoryBlock) 
                    and proxy_ip IN (SELECT id FROM proxies_ipv4 pr
                      WHERE dead = 0 and static = 1 and active = 1 and country = :country 
                      $whereLocation $whereSource) 
                    and proxy_ip NOT IN (SELECT proxy_ip FROM user_ports WHERE category = :categorySneaker) 
                    and proxy_ip NOT IN (SELECT proxy_id FROM user_ports_frozen) 
                    GROUP BY c_class
                    ORDER BY rand()
                ) as pr
                $joinSubnet as usr ON pr.c_class = usr.c_class
                WHERE $whereOnSubnetC
                ORDER BY (user_count / system_count), system_count DESC
                LIMIT :limit";

            $result = $this->getCachedQueryResultColumn($sql, $queryArgs, $queryTypes);

            if ($result) {
                $this->log("Found sneaker id $result by \"double sell blocks\" logic case");

                return $result;
            }
        }

        // Double sell as last resort is only allowed for US based proxies
        if ($this->getFromConfig('sneaker.doubleSellStatic.enabled') and Port::COUNTRY_US == $port->getCountry()) {
            // Double sell static proxies
            $sql = "SELECT id
                FROM (
                SELECT proxy_ip as id,
                    count(*) as system_count,
                    substring_index( proxy_ip, '.', 3 ) as c_class
                    FROM user_ports 
                    WHERE country = :countryUS and category IN (:categorySemi, :categoryDedi) 
                    and proxy_ip IN (SELECT id FROM proxies_ipv4 pr
                      WHERE dead = 0 and static = 1 and active = 1 and country = :country 
                      $whereLocation $whereSource) 
                    and proxy_ip NOT IN (SELECT proxy_ip FROM user_ports WHERE category = :categorySneaker) 
                    and proxy_ip NOT IN (SELECT proxy_id FROM user_ports_frozen) 
                    GROUP BY proxy_ip, c_class
                    HAVING count(*) <= 2 
                    ORDER BY rand()
                ) as pr
                $joinSubnet as usr ON pr.c_class = usr.c_class
                WHERE $whereOnSubnetC
                ORDER BY (user_count / system_count), system_count DESC
                LIMIT :limit";

            $queryArgs = array_merge($queryArgs, [
                'maxPerId' => $this->getFromConfig('sneaker.doubleSellStatic.maxPerIp')
            ]);

            $result = $this->getCachedQueryResultColumn($sql, $queryArgs, array_merge($queryTypes, ['maxPerId' => \PDO::PARAM_INT]));

            if ($result) {
                $this->log("Found sneaker id $result by \"double sell static last resort\" logic case");
            }
        }

        return $result;
    }

    public function findRandomSemiDedicatedProxy(Port $port)
    {
        $semiCount = $this->getRule('semi.groupQuantity', $port);
        $semiCountInCountry = $this->getRule('semi.groupQuantityCountries', $port);
        if (!empty($semiCountInCountry[$port->getCountry()])) {
            $semiCount = $semiCountInCountry[$port->getCountry()];
        }
        $queryArgs = [
            'country'         => $port->getCountry(),
            'regionId'        => $port->getRegionId(),
            'userType'        => $port->getUserType(),
            'userId'          => $port->getUserId(),
            'semiCount'       => $semiCount,
            'categoryDedi'    => Port::toOldCategory(Port::CATEGORY_DEDICATED),
            'categorySemi'    => Port::toOldCategory(Port::CATEGORY_SEMI_DEDICATED),
            'categorySneaker' => Port::CATEGORY_SNEAKER,
            'categoryKushang' => Port::CATEGORY_KUSHANG,
            'categorySupreme' => Port::CATEGORY_SUPREME,
            'categoryMapple'  => Port::CATEGORY_MAPPLE,
            'limit'           => $this->getFromConfig('load.default')
        ];
        $queryTypes = ['semiCount' => \PDO::PARAM_INT, 'limit' => \PDO::PARAM_INT];

        $whereCountry = 'and pr.country = :country';
        $whereLocation = '';
        $whereDate = '';
        $whereNotHostLocation = '';

        if (Port::COUNTRY_US == $port->getCountry()) {
            if ($port->getRegionId() and 1 != $port->getRegionId()) {
                $whereLocation = 'and pr.region_id = :regionId';
            }

            $whereDate = "and date_added < :sneakerDate";
            $queryArgs['sneakerDate'] = $this->getFromConfig('sneakerDate');
        }
        elseif (Port::COUNTRY_INTERNATIONAL == $port->getCountry()) {
            $whereCountry = 'and pr.country != :countryUS';
            $queryArgs['countryUS'] = Port::COUNTRY_US;
            if ($port->getRegionId() and !in_array($port->getRegionId(), [1, 32])) {
                $whereLocation = 'and pr.region_id = :regionId';
            }
        }

        if ($locations = $this->getRule('semi.exclude.hostLocations', $port)) {
            $whereNotHostLocation = 'AND pr.host_loc NOT IN(:notHostLocations)';
            $queryArgs['notHostLocations'] = $locations;
            $queryTypes['notHostLocations'] = Connection::PARAM_STR_ARRAY;
        }

        // Double up customers proxies
        if ($this->getRule('semi.onTopUsers.enabled', $port) and
            in_array($port->getUserId(), $this->getRule('semi.onTopUsers.users', $port))) {
            $sql = "SELECT id, count
            FROM (
                SELECT substring_index( ip, '.', 1 ) as a_class,
                substring_index( ip, '.', 2 ) as b_class,
                substring_index( ip, '.', 3 ) as c_class,
                count(*) as system_count,
                max(id) as id,
                count
                FROM (
                    SELECT pr.id, ip, pr.last_used, COUNT(up.id) as count
                    FROM  proxies_ipv4 pr
                    LEFT JOIN user_ports up on up.proxy_ip = pr.id
                    WHERE pr.active 
                    AND pr.dead = 0
                    AND pr.static = 1
                    $whereNotHostLocation $whereCountry $whereLocation $whereDate
                    AND pools is null
                    AND pr.host_loc != 'los angeles, ca' AND host_loc != 'los angeles 2'
                    and (up.country = :country and up.category = :categorySemi)                    
                    and pr.id IN (SELECT proxy_ip FROM user_ports WHERE category = :categorySemi)
                    and pr.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                    and pr.id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_type = :userType and user_id = :userId)
                    and pr.id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                    AND pr.id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                    GROUP BY pr.id
                    ORDER BY count DESC
                ) as pr2
                GROUP BY a_class, b_class, c_class
                ORDER BY last_used DESC
            ) as pr
            LEFT JOIN (
                SELECT
                substring_index( ip, '.', 1 ) as a_class,
                substring_index( ip, '.', 2 ) as b_class,
                substring_index( ip, '.', 3 ) as c_class,
                count(*) as user_count
                FROM  `proxies_ipv4` pr
                LEFT JOIN user_ports up on up.proxy_ip = pr.id
                WHERE up.user_type = :userType and up.user_id = :userId
                GROUP BY a_class, b_class, c_class
            ) as usr ON pr.a_class = usr.a_class and pr.b_class = usr.b_class and pr.c_class = usr.c_class
            ORDER BY count DESC, (user_count / system_count)
            LIMIT :limit";

            $result = $this->getCachedQueryResult($sql, $queryArgs, $queryTypes, function(Statement $stmt) {
                return $stmt->fetchColumn();
            });

            if ($result) {
                $this->log("Found semi-3 id $result by \"on top\" logic case");

                return $result;
            }
        }

        $placeholders = [
            'where' => [
                'notHostLocation' => $whereNotHostLocation,
                'isCountry' => $whereCountry,
                'isLocation' => $whereLocation,
                'isData' => $whereDate,
                'notPrevious' =>
                    'AND pr.id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                    AND pr.id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)'
            ],
            'having' => [
                'maxOnPorts' => 'COUNT(up.id) < :semiCount AND COUNT(up.id) >= 1'
            ]
        ];

        $sql = "SELECT id, count
            FROM (
                SELECT substring_index( ip, '.', 1 ) as a_class,
                substring_index( ip, '.', 2 ) as b_class,
                substring_index( ip, '.', 3 ) as c_class,
                count(*) as system_count,
                max(id) as id,
                count
                FROM (
                    SELECT pr.id, ip, pr.last_used, COUNT(up.id) as count
                    FROM  proxies_ipv4 pr
                    LEFT JOIN user_ports up on up.proxy_ip = pr.id
                    WHERE pr.active 
                    AND pr.dead = 0
                    AND pr.static = 1
                    :whereNotHostLocation :whereIsCountry :whereIsLocation :whereIsData
                    AND pools is null                    
                    and ((up.country = :country and up.category = :categorySemi) or up.category is null or up.category = :categoryKushang)
                    and pr.id NOT IN (SELECT proxy_ip FROM user_ports 
                      WHERE category IN (:categoryDedi, :categorySneaker, :categorySupreme, :categoryMapple))
                    and pr.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                    and pr.id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_type = :userType and user_id = :userId)
                    :whereNotPrevious
                    GROUP BY pr.id
                    HAVING :havingMaxOnPorts
                    ORDER BY count DESC
                ) as pr2
                GROUP BY a_class, b_class, c_class
                ORDER BY last_used DESC
            ) as pr
            LEFT JOIN (
                SELECT
                substring_index( ip, '.', 1 ) as a_class,
                substring_index( ip, '.', 2 ) as b_class,
                substring_index( ip, '.', 3 ) as c_class,
                count(*) as user_count
                FROM  `proxies_ipv4` pr
                LEFT JOIN user_ports up on up.proxy_ip = pr.id
                WHERE up.user_type = :userType and up.user_id = :userId
                GROUP BY a_class, b_class, c_class
            ) as usr ON pr.a_class = usr.a_class and pr.b_class = usr.b_class and pr.c_class = usr.c_class
            ORDER BY count DESC, (user_count / system_count)
            LIMIT :limit";

        // With history, do not move dedi
        $result = $this->getCachedQueryResultColumn($this->injectSqls($sql, $placeholders), $queryArgs, $queryTypes);

        if ($result) {
            $this->log("Found semi-3 id $result by \"common, no dedi, with history\" logic case");

            return $result;
        }

        // Without history, do not move dedi
        $result = $this->getCachedQueryResultColumn($this->injectSqls($sql, array_replace_recursive($placeholders, [
            'where' => ['notPrevious' => '']
        ])), $queryArgs, $queryTypes);

        if ($result) {
            $this->log("Found semi-3 id $result by \"common, no dedi, without history\" logic case");

            return $result;
        }

        // With history, move dedi
        $result = $this->getCachedQueryResultColumn($this->injectSqls($sql, array_replace_recursive($placeholders, [
            'having' => ['maxOnPorts' => 'COUNT(up.id) < :semiCount']
        ])), $queryArgs, $queryTypes);

        if ($result) {
            $this->log("Found semi-3 id $result by \"common, move dedi, with history\" logic case");

            return $result;
        }

        return false;
    }

    public function findRandomMappleProxy(Port $port)
    {
        $sql = "SELECT id FROM (
                SELECT *
                FROM proxies_ipv4 as prox
                WHERE substring_index( prox.ip, '.', 3 ) NOT IN (
                    SELECT DISTINCT substring_index( ip, '.', 3 ) as ip
                    FROM proxies_ipv4 p
                    JOIN user_ports up ON p.id = up.proxy_ip
                    WHERE user_type = :userType and user_id = :userId and p.country = :country and category = :mappleCategory
                ) AND substring_index( prox.ip, '.', 3 ) NOT IN (
                    SELECT DISTINCT substring_index( ip, '.', 3 ) as c_class FROM maple_ips
                )
                AND country = :country and static = 1 and active = 1
                LIMIT 5
            ) as tester
            ORDER BY rand()
            LIMIT 1";

        return $this->conn->executeQuery($sql, [
            'country'  => $port->getCountry(),
            'userType' => $port->getUserType(),
            'userId'   => $port->getUserId(),
            'mappleCategory' => Port::CATEGORY_MAPPLE
        ])->fetchColumn();
    }

    public function findRandomSupremeProxy(Port $port)
    {
        // Double sell dedi and semi proxies (1 proxy per supreme/sneaker, 2 proxies per dedi+supreme)
        $sql = "SELECT p.id
            FROM `proxies_ipv4` p 
            WHERE
              dead = 0 and static = 1 and active = 1 and country = :country
              AND (
                  source LIKE 'budgetvm-la-%' 
                  OR ip IN (SELECT ip FROM onlineTools.proxy_tester WHERE supremenewyork_working = 1)
                  OR source = 'budgetvm-test-1'
                  OR source = 'nexeon-chicago-1'
              )
              AND id IN (SELECT proxy_ip FROM user_ports WHERE category IN (:categoryDedi, :categorySemi) and country = :country)
              AND id NOT IN (SELECT proxy_ip FROM user_ports WHERE category IN (:categorySneaker, :categorySupreme) and country = :country)
              and id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
              and id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_type = :userType and user_id = :userId)
              AND id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
              AND id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
              LIMIT :limit";

        $result = $this->getCachedQueryResult($sql, [
            'country'         => $port->getCountry(),
            'userType'        => $port->getUserType(),
            'userId'          => $port->getUserId(),
            'categorySneaker' => Port::CATEGORY_SNEAKER,
            'categorySupreme' => Port::CATEGORY_SUPREME,
            'categoryDedi'    => Port::toOldCategory(Port::CATEGORY_DEDICATED),
            'categorySemi'    => Port::CATEGORY_SEMI_DEDICATED,
            'limit'           => $this->getFromConfig('load.default')
        ], ['limit' => \PDO::PARAM_INT], function(Statement $stmt) {
            return $stmt->fetchColumn();
        });

        if ($result) {
            return $result;
        }

        // Available supreme proxies
        $sql = "SELECT id
            FROM (
                SELECT substring_index( ip, '.', 1 ) as a_class,
                substring_index( ip, '.', 2 ) as b_class,
                substring_index( ip, '.', 3 ) as c_class,
                count(*) as system_count,
                id
                FROM proxies_ipv4 pr
                WHERE dead = 0 and static = 1 and active = 1 and country = :country				
                AND  (
                    ip IN (SELECT ip FROM onlineTools.proxy_tester WHERE supremenewyork_working = 1)
                    OR source = 'budgetvm-test-1'
                    OR source = 'nexeon-chicago-1'
                )
                AND id NOT IN (SELECT proxy_ip FROM `user_ports` WHERE category IN (:categorySupreme, :categorySneaker))
                and id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                and id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_type = :userType and user_id = :userId)
                and id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                AND id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
                GROUP BY a_class, b_class, c_class
                ORDER BY last_used DESC
            ) as pr
            LEFT JOIN (
                SELECT
                substring_index( ip, '.', 1 ) as a_class,
                substring_index( ip, '.', 2 ) as b_class,
                substring_index( ip, '.', 3 ) as c_class,
                count(*) as user_count
                FROM  `proxies_ipv4` pr
                LEFT JOIN user_ports up on up.proxy_ip = pr.id
                WHERE up.user_type = :userType and up.user_id = :userId
                GROUP BY a_class, b_class, c_class
            ) as usr ON pr.a_class = usr.a_class and pr.b_class = usr.b_class and pr.c_class = usr.c_class
            ORDER BY (user_count / system_count)
            LIMIT :limit";

        $result = $this->getCachedQueryResult($sql, [
            'country'         => $port->getCountry(),
            'userType'        => $port->getUserType(),
            'userId'          => $port->getUserId(),
            'categorySneaker' => Port::CATEGORY_SNEAKER,
            'categorySupreme' => Port::CATEGORY_SUPREME,
            'limit'           => $this->getFromConfig('load.default')
        ], ['limit' => \PDO::PARAM_INT], function(Statement $stmt) {
            return $stmt->fetchColumn();
        });

        if ($result) {
            return $result;
        }

        // Double sell sneaker proxies - last resort
        $sql = "SELECT p.id
            FROM `proxies_ipv4` p 
            WHERE
              dead = 0 and static = 1 and active = 1 and country = :country
              AND (
                  source LIKE 'budgetvm-la-%' 
                  OR ip IN (SELECT ip FROM onlineTools.proxy_tester WHERE supremenewyork_working = 1)
                  OR source = 'budgetvm-test-1'
                  OR source = 'nexeon-chicago-1'
              )
              AND id IN (SELECT proxy_ip FROM user_ports WHERE category IN (:categorySneaker) and country = :country)
              AND id NOT IN (SELECT proxy_ip FROM user_ports WHERE category IN (:categorySupreme) and country = :country)
              and id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
              and id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_type = :userType and user_id = :userId)
              AND id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
              AND id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
              LIMIT :limit";

        $result = $this->getCachedQueryResult($sql, [
            'country'         => $port->getCountry(),
            'userType'        => $port->getUserType(),
            'userId'          => $port->getUserId(),
            'categorySneaker' => Port::CATEGORY_SNEAKER,
            'categorySupreme' => Port::CATEGORY_SUPREME,
            'limit'           => $this->getFromConfig('load.default')
        ], ['limit' => \PDO::PARAM_INT], function(Statement $stmt) {
            return $stmt->fetchColumn();
        });

        return $result;
    }

    public function findRandomKushangProxy(Port $port)
    {
        $whereDate = '';
        $queryArgs = [
            'userId' => $port->getUserId(),
            'userType' => $port->getUserType(),
            'countryUs' => Port::COUNTRY_US,
            'categorySemi' => Port::CATEGORY_SEMI_DEDICATED,
            'categoryRotating' => Port::toOldCategory(Port::CATEGORY_ROTATING),
            'categorySneaker' => Port::CATEGORY_SNEAKER
        ];

        if (Port::COUNTRY_US == $port->getCountry()) {
            $whereDate = "and pr.date_added < :sneakerDate";
            $queryArgs['sneakerDate'] = $this->getFromConfig('sneakerDate');
        }

        $sql = "SELECT pr.id, COUNT(up.id) as count
            FROM proxies_ipv4 pr
            LEFT JOIN user_ports up on up.proxy_ip = pr.id
            WHERE pr.active = 1 AND pr.dead = 0 AND pr.country = :countryUs
            $whereDate
            and (up.category IS NULL or 
              up.category = :categorySemi or up.category = :categoryRotating or up.category = :categorySneaker)
            and pr.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
            and pr.id NOT IN (SELECT proxy_id FROM user_ports_frozen WHERE user_type = :userType and user_id = :userId)
            and pr.id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
            AND pr.id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id = :userId)
            AND ip NOT IN (SELECT ip FROM assigner_ipv4_kushang_blacklist_ip)
            GROUP BY pr.id
            ORDER BY count DESC, rand()
            LIMIT 100";

        return $this->getCachedQueryResult($sql, $queryArgs, [], function(Statement $stmt) {
            return $stmt->fetchColumn();
        });

    }

    public function findRandomRotatingProxy(Port $port)
    {
        return $this->getRotationAdviser('rotating')->findRandomProxy($port);
    }

    /**
     * @return SpecialCustomerAdviser
     */
    public function getSpecialCustomerAdviser()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getRotationAdviser('special');
    }

    // --- Util

    /**
     * @param $category
     * @return AbstractRotationAdviser|KushangRotationAdviser|RotatingRotationAdviser
     * @throws \ErrorException
     */
    protected function getRotationAdviser($category)
    {
        if (empty($this->externalAdvisers[ $category ])) {
            if (!isset($this->externalAdvisersMap[ $category ])) {
                throw new \ErrorException("Rotation Adviser \"$category\" is unknown!");
            }

            $cls = $this->externalAdvisersMap[ $category ];
            $this->externalAdvisers[ $category ] = new $cls($this->conn, $this);
        }

        return $this->externalAdvisers[ $category ];
    }

    public function getRule($key, Port $port)
    {
        $rule = $this->getFromConfig("rules.$key");
        $specificRules = $this->getFromConfig('rulesPerUser');

        // Personalization
        if (!empty($specificRules[$port->getUserId()])) {
            $specificRule = Arr::getNested($specificRules[$port->getUserId()], $key);
            if (isset($specificRule)) {
                if (is_array($rule) and is_array($specificRule)) {
                    $this->log('Specific rule override', [
                        'original' => $rule,
                        'specific' => $specificRule,
                        'result' => array_replace_recursive($rule, $specificRule)
                    ]);
                    $rule = array_replace_recursive($rule, $specificRule);
                }
                elseif (!is_array($rule) and !is_array($specificRule)) {
                    $rule = $specificRule;
                }
                else {
                    $this->log('Specific rule specification does not match with original rule', [
                        'original' => $rule,
                        'specific' => $specificRule
                    ]);
                }
            }
        }

        return $rule;
    }
}
