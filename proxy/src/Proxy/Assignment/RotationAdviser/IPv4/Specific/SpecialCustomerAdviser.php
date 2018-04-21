<?php

namespace Proxy\Assignment\RotationAdviser\IPv4\Specific;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\Assignment\Port\PortInterface;
use Proxy\Assignment\RotationAdviser\AbstractSpecialCustomerAdviser;
use Proxy\Assignment\RotationAdviser\IPv4\RotationAdviser;
use Proxy\Assignment\RotationAdviser\SpecialCustomerRuleBuilder;

class SpecialCustomerAdviser extends AbstractSpecialCustomerAdviser
{

    protected function getHandlers()
    {
        $handlers = [
            SpecialCustomerRuleBuilder::getBuilder()
                ->setName('Moved blocks')
                ->setCustomCondition([
                    // qwswjkawwt
                    ['userId' => 17824, 'blocks' => [
                        // dedicated44
                        '162.223.122.0/24', '168.245.206.0/24', '148.59.184.0/24', '148.59.185.0/24',
                        // dedicated45
                        '66.97.179.0/24', '23.160.128.0/24', '67.226.219.0/24', '148.59.146.0/24'
                    ]],
                    // test4038
                    ['userId' => 17825, 'blocks' => ['52.128.31.0/24']],
                    // semclean
                    ['userId' => 17826, 'blocks' => ['208.103.166.0/24', '216.163.199.0/24']],
                    // 173.254.203.116
                    ['userId' => 17827, 'blocks' => ['162.223.122.0/24']],
                    // 204.152.215.114
                    ['userId' => 17828, 'blocks' => ['52.128.31.0/24']],
                    // 116.108.159.34
                    ['userId' => 17829, 'blocks' => ['52.128.31.0/24']],

                    // New block packages
                    ['userId' => 17359, 'blocks' => ['139.60.101.0/24', '216.163.199.0/24']],
                    ['userId' => 19276, 'blocks' => [
                        '208.103.166.0/24',
                        '216.230.30.0/24',
                        '216.230.31.0/24',
                        '147.92.52.0/24',
                        '147.92.53.0/24',
                        '147.92.55.0/24',
                    ]],
                    // jdavi 2
                    ['userId' => 19579, 'blocks' => [
                        // old blocks V

                        // dedicated44
                        '162.223.122.0/24', '168.245.206.0/24', '148.59.184.0/24', '148.59.185.0/24',
                        // dedicated45
                        '66.97.179.0/24', '23.160.128.0/24', '67.226.219.0/24', '148.59.146.0/24',

                        // new blocks V

                        '170.199.224.0/24',
                        '170.199.225.0/24',
                        '170.199.226.0/24',
                        '170.199.227.0/24',
                        '170.199.228.0/24',
                        '170.199.229.0/24',
                        '170.199.230.0/24',
                        '170.199.231.0/24',
                    ]],
                    ['userId' => 17618, 'blocks' => ['68.65.221.0/24', '68.65.222.0/24', '68.65.223.0/24']],
                    // cosmin
                    ['userId' => 16361, 'blocks' => ['147.92.54.0/24', '139.60.101.0/24', '216.163.199.0/24', '207.182.31.0/24']],
                ], function(PortInterface $port, array $conditions) {
                    if (Port::INTERNET_PROTOCOL != $port->getIpV() or
                        !('block' == $port->getType() or Port::CATEGORY_BLOCK == $port->getCategory())) {
                        return false;
                    }
                    foreach ($conditions as $condition) {
                        if ($port->getUserId() == $condition['userId']) {
                            return $condition;
                        }
                    }

                    return false;
                })
                ->setFallbackToCommonAdviser(false)
                ->setHandler(function(PortInterface $port, array $context) {
                    $blocks = $context['blocks'];

                    $sql = "
                            SELECT id
                            FROM proxies_ipv4 p
                            WHERE dead = 0 AND block IN (:blocks)
                              AND p.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_id = :userId)  
                            ORDER BY p.ip
                                                    
                        ";
                    return $this->getCachedQueryResultColumn($sql,
                        ['userId' => $port->getUserId(), 'blocks' => $blocks],
                        ['blocks' => Connection::PARAM_STR_ARRAY]
                    );
                }),
            SpecialCustomerRuleBuilder::getBuilder()
                ->setName('Ignored users')
                ->setCustomCondition([

                ], function(PortInterface $port, array $conditions) {
                    return Port::INTERNET_PROTOCOL == $port->getIpV() and in_array($port->getUserId(), $conditions);
                })
                ->setFallbackToCommonAdviser(false)
                ->setHandler(function(PortInterface $port, array $context) {
                    return false;
                }),
            SpecialCustomerRuleBuilder::getBuilder()
                ->setName('adthena assignment')
                ->setCustomCondition([18592], function(PortInterface $port, array $conditions) {
                    return Port::INTERNET_PROTOCOL == $port->getIpV() and in_array($port->getUserId(), $conditions);
                })
                ->setFallbackToCommonAdviser(true)
                ->setHandler(function(PortInterface $port, array $context) {
                    $sql = "
                            SELECT id
                            FROM proxies_ipv4 p
                            WHERE dead = 0 AND host_loc = 'us-block'
                              AND p.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_id = :userId)                            
                        ";
                    return $this->getCachedQueryResultColumn($sql, ['userId' => $port->getUserId()], []);
                }),
            SpecialCustomerRuleBuilder::getBuilder()
                ->setName('Double sell small customers packages')
                ->setCustomCondition([
                    [
                        'userId'   => 19260,
                        'country'  => Port::COUNTRY_US,
                        'category' => Port::CATEGORY_SEMI_DEDICATED,
                        'fallback' => true
                    ]
                ], function(PortInterface $port, array $conditions) {
                    foreach ($conditions as $condition) {
                        if ($port->getUserId() == $condition['userId'] and
                            in_array($port->getCountry(), (array) $condition['country']) and
                            in_array($port->getCategory(), (array) $condition['category'])) {
                            return $condition;
                        }

                    }

                    return false;
                })
                ->setFallbackToCommonAdviser(true)
                ->setHandler(function(Port $port, array $context) {
                    $commonAdviser = new RotationAdviser($this->conn, $this->logger);

                    $queryArgs = [
                        'country'  => $port->getCountry(),
                        'userType' => $port->getUserType(),
                        'userId'   => $port->getUserId(),
                        'regionId' => $port->getRegionId(),
                        'limit'    => $commonAdviser->getFromConfig('load.default'),
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
                        $queryArgs['sneakerDate'] = $commonAdviser->getFromConfig('sneakerDate');
                    }
                    elseif (Port::COUNTRY_INTERNATIONAL == $port->getCountry()) {
                        $whereCountry = 'and pr.country != :countryUS';
                        $queryArgs['countryUS'] = Port::COUNTRY_US;
                        if ($port->getRegionId() and !in_array($port->getRegionId(), [1, 32])) {
                            $whereLocation = 'and pr.region_id = :regionId';
                        }
                    }

                    if ($locations = $commonAdviser->getRule('dedicated.exclude.hostLocations', $port)) {
                        $whereNotHostLocation = 'AND pr.host_loc NOT IN(:notHostLocations)';
                        $queryArgs['notHostLocations'] = $locations;
                        $queryTypes['notHostLocations'] = Connection::PARAM_STR_ARRAY;
                    }

                    $whereNotHostServer = '';
                    if ($excludeServers = $commonAdviser->getRule('dedicated.exclude.servers', $port)) {
                        $whereNotHostServer = [];
                        foreach ($excludeServers as $i => $serverName) {
                            $whereNotHostServer[] = "ps.name NOT LIKE :notServer$i";
                            $queryArgs["notServer$i"] = $serverName;
                        }
                        $whereNotHostServer = $whereNotHostServer ? ('AND (' . join(' AND ', $whereNotHostServer) . ')') : '';
                    }

                    $sql = "
                        SELECT id
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
                                    SELECT user_id, proxy_ip, package_id, 
                                      count(id) as ports, count(id_doubled) as ports_doubled
                                    FROM (
                                        SELECT pup.user_id, pup.id as package_id, up.proxy_ip, up.id, upd.id as id_doubled
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
                                        ORDER BY RAND()
                                    ) t
                                    GROUP BY package_id
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
                        'maxPackagePorts' => $commonAdviser->getRule('dedicated.doubleSellPackages.maxPackagePorts', $port),
                        'maxPerPort' => $commonAdviser->getRule('dedicated.doubleSellPackages.perPackage', $port) - 1,
                        // The last customer will be double sold N times - a flaw of such method
                        'limit' => 5
                    ]), array_merge($queryTypes, [
                        'maxPackagePorts' => \PDO::PARAM_INT,
                        'maxPerPort' => \PDO::PARAM_INT
                    ]));

                    return $result;
                }),
            // Assign the block
            [
                function (Port $port) {
                    return $port->getUserId() == 12339 and
                        Port::COUNTRY_GB == $port->getCountry() and Port::CATEGORY_SNEAKER == $port->getCategory();
                },
                function (Port $port) {
                    $queryArgs = [
                        'country'  => $port->getCountry(),
                        'userType' => $port->getUserType(),
                        'userId'   => $port->getUserId(),
                        'location' => '',
                        'limit'    => $this->baseAdviser->getFromConfig('load.default'),

                        'categorySneaker' => Port::CATEGORY_SNEAKER,
                        'categorySupreme' => Port::CATEGORY_SUPREME,
                        'categoryDedi'    => Port::toOldCategory(Port::CATEGORY_DEDICATED),
                        'categorySemi'    => Port::CATEGORY_SEMI_DEDICATED,
                        'countryUS'       => Port::COUNTRY_US,
                    ];
                    $queryTypes = ['limit' => \PDO::PARAM_INT];
                    $joinSubnet = "LEFT JOIN (
                        SELECT substring_index( ip, '.', 3 ) as c_class,
                        count(*) as user_count
                        FROM  proxies_ipv4 pr
                        LEFT JOIN user_ports up on up.proxy_ip = pr.id
                        WHERE up.user_type = :userType and up.user_id = :userId
                        GROUP BY c_class
                    )";
                    $whereSource = '';
                    $whereBlock = ' and block = "185.133.74.0/24"';
                    $queryArgs['location'] = 'london';

                    $sql = "SELECT id
                        FROM (
                            SELECT id,
                            count(*) as system_count,
                            substring_index( ip, '.', 3 ) as c_class
                            FROM proxies_ipv4 pr
                            WHERE dead = 0 and static = 1 and active = 1 and country = :country and host_loc = :location
                            $whereSource $whereBlock
                            AND id NOT IN (SELECT proxy_ip FROM user_ports WHERE proxy_ip IS NOT NULL)
                            and id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                            GROUP BY c_class
                            ORDER BY last_used DESC
                        ) as pr
                        $joinSubnet as usr ON pr.c_class = usr.c_class
                        ORDER BY (user_count / system_count), system_count DESC
                        LIMIT :limit";

                    return $this->getCachedQueryResult($sql, $queryArgs, $queryTypes, function(Statement $stmt) {
                        return $stmt->fetchColumn();
                    });
                }
            ],

            // Assign the block
            [
                function (Port $port) {
                    return $port->getUserId() == 12466 and
                        Port::COUNTRY_US == $port->getCountry() and Port::CATEGORY_SNEAKER == $port->getCategory();
                },
                function(Port $port) {
                    $queryArgs = [
                        'country'  => $port->getCountry(),
                        'userType' => $port->getUserType(),
                        'userId'   => $port->getUserId(),
                        'location' => '',
                        'limit'    => $this->baseAdviser->getFromConfig('load.default'),

                        'categorySneaker' => Port::CATEGORY_SNEAKER,
                        'categorySupreme' => Port::CATEGORY_SUPREME,
                        'categoryDedi'    => Port::toOldCategory(Port::CATEGORY_DEDICATED),
                        'categorySemi'    => Port::CATEGORY_SEMI_DEDICATED,
                        'countryUS'       => Port::COUNTRY_US,
                    ];
                    $queryTypes = ['limit' => \PDO::PARAM_INT];
                    $joinSubnet = "LEFT JOIN (
                        SELECT substring_index( ip, '.', 3 ) as c_class,
                        count(*) as user_count
                        FROM  proxies_ipv4 pr
                        LEFT JOIN user_ports up on up.proxy_ip = pr.id
                        WHERE up.user_type = :userType and up.user_id = :userId
                        GROUP BY c_class
                    )";
                    $whereBlock = ' and block = "104.202.211.0/24"';

                    $sql = "SELECT id
                        FROM (
                            SELECT id,
                            count(*) as system_count,
                            substring_index( ip, '.', 3 ) as c_class
                            FROM proxies_ipv4 pr
                            WHERE dead = 0 and static = 1 and active = 1 and country = :country
                            $whereBlock
                            AND id NOT IN (SELECT proxy_ip FROM user_ports WHERE proxy_ip IS NOT NULL)
                            and id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                            GROUP BY c_class
                            ORDER BY last_used DESC
                        ) as pr
                        $joinSubnet as usr ON pr.c_class = usr.c_class
                        ORDER BY (user_count / system_count), system_count DESC
                        LIMIT :limit";

                    return $this->getCachedQueryResult($sql, $queryArgs, $queryTypes, function(Statement $stmt) {
                        return $stmt->fetchColumn();
                    });
                }
            ],

            // shazim on top of rotating, semi-3 or instagress
            [
                function (Port $port) {
                    return false;
                    // return in_array($port->getUserId(), [15122, 18627]) and
                    //    Port::COUNTRY_US == $port->getCountry() and Port::CATEGORY_SEMI_DEDICATED == $port->getCategory();
                },
                function(Port $port) {
                    $queryArgs = [
                        'country'         => $port->getCountry(),
                        'regionId'        => $port->getRegionId(),
                        'userType'        => $port->getUserType(),
                        'userId'          => $port->getUserId(),
                        'categoryDedi'    => Port::toOldCategory(Port::CATEGORY_DEDICATED),
                        'categorySemi'    => Port::toOldCategory(Port::CATEGORY_SEMI_DEDICATED),
                        'categoryRotating'    => Port::toOldCategory(Port::CATEGORY_ROTATING),
                        'categorySneaker' => Port::CATEGORY_SNEAKER,
                        'categoryKushang' => Port::CATEGORY_KUSHANG,
                        'categorySupreme' => Port::CATEGORY_SUPREME,
                        'categoryMapple'  => Port::CATEGORY_MAPPLE,
                        'limit'           => $this->baseAdviser->getFromConfig('load.default')
                    ];
                    $queryTypes = ['limit' => \PDO::PARAM_INT];

                    $whereCountry = 'and pr.country = :country';
                    $whereLocation = '';

                    if (Port::COUNTRY_US == $port->getCountry()) {
                        if ($port->getRegionId() and 1 != $port->getRegionId()) {
                            $whereLocation = 'and pr.region_id = :regionId';
                        }
                    }

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
                                WHERE pr.active = 1
                                AND pr.dead = 0
                                $whereCountry $whereLocation
                                AND pools is null
                                AND pr.host_loc != 'los angeles, ca'
                                and pr.id IN (SELECT proxy_ip FROM user_ports WHERE category IN (:categoryRotating, :categorySemi) OR user_id = 1)
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
                        $this->log("Found semi-3 id $result by \"special\" logic case for 15122 user");
                    }

                    return $result;
                },
                // in case of fail just continue other methods
                true
            ],

            // Dedicated on top of other users
            call_user_func(function() {
                $rules = [
                    [
                        // jdavi@diffbot.com
                        'for' => 13009,
                        // ocrexpert@hotmail.com, Leger proxies, instagress
                        'onTop' => [11433, 7893, 3133],
                        'backup' => false
                    ],
                    [
                        // iande@getstat.com
                        'for' => 15540,
                        // Tobias
                        'onTop' => [3133],
                        'backup' => false
                    ],
                    [
                        // Instagress
                        'for' => 3133,
                        // Leger
                        'onTop' => [
                            // Leger
                            7893,
                            // Tobias
                            11818, 11819, 11820, 11821, 11822, 13543, 5915
                        ],
                        'backup' => false
                    ],
                    [
                        // Tobias
                        'for' => [11818, 11819, 11820, 11821, 11822, 13543, 5915],
                        // Instagress, leger, and someone else
                        'onTop' => [3133, 7893, 6816],
                        // Adthena duplicated account
                        'ignore' => [19087],
                        'backup' => false
                    ],
                    [
                        // Yun, dhdirhkd@naver.com
                        'for' => 13169,
                        'onTop' => [
                            // Tobias
                            11818, 11819, 11820, 11821, 11822, 13543, 5915,

                            // Leger
                            7893,

                            // other users
                            16016, 2898
                        ],
                        'backup' => false
                    ],
                    /*[
                        // darren@whitespark.ca
                        'for' => 17359,
                        // Instagress
                        'onTop' => [3133],
                        'backup' => false
                    ],*/
                    [
                        // zcnh.fy@gmail.com
                        'for' => 17422,
                        // Instagress
                        'onTop' => [3133],
                        'backup' => false
                    ],
                    [
                        // a.cherny@semrush.com
                        'for' => 17722,
                        // Instagress
                        'onTop' => [3133],
                        'backup' => false
                    ],
                    [
                        // shazim
                        'for' => 18627,
                        // Different big customers
                        'onTop' => [7893, 8913, 11890, 13169, 3358, 17353, 18819],
                        'backup' => false
                    ],
                    [
                        // paul.felby@adthena.com
                        'for' => 18592,
                        // instagress, leger, fuelgram, igerslike
                        'onTop' => [3133, 7893, 16862, 15753],
                        'backup' => true
                    ],
                    [
                        // cosmin@seomonitor.com
                        'for' => 16361,
                        // instagress
                        'onTop' => [3133],
                        'backup' => false
                    ],
                ];

                $getRules = function(Port $port) use ($rules) {
                    // Only for dedicated
                    if (!(Port::COUNTRY_US == $port->getCountry() and Port::CATEGORY_DEDICATED == $port->getCategory())) {
                        return false;
                    }

                    foreach ($rules as $rule) {
                        if (in_array($port->getUserId(), (array) $rule['for'])) {
                            return $rule;
                        }
                    }

                    // Not found
                    return false;
                };

                return [

                    // Check conditions
                    function(Port $port) use ($getRules) {
                        return !!$getRules($port);
                    },

                    // Handler
                    function(Port $port) use ($getRules) {
                        $rule = $getRules($port);
                        if (!$rule) {
                            return false;
                        }

                        $queryArgs = [
                            'country'          => $port->getCountry(),
                            'regionId'         => $port->getRegionId(),
                            'userType'         => $port->getUserType(),
                            'userId'           => $port->getUserId(),
                            'userAllIds'          => (array) $rule[ 'for' ],
                            'categoryDedi'     => Port::toOldCategory(Port::CATEGORY_DEDICATED),
                            'categorySemi'     => Port::toOldCategory(Port::CATEGORY_SEMI_DEDICATED),
                            'categoryRotating' => Port::toOldCategory(Port::CATEGORY_ROTATING),
                            'categorySneaker'  => Port::CATEGORY_SNEAKER,
                            'categoryKushang'  => Port::CATEGORY_KUSHANG,
                            'categorySupreme'  => Port::CATEGORY_SUPREME,
                            'categoryMapple'   => Port::CATEGORY_MAPPLE,
                            'limit'            => $this->baseAdviser->getFromConfig('load.default'),

                            'usersOn' => $rule[ 'onTop' ]
                        ];
                        $queryTypes = [
                            'limit'   => \PDO::PARAM_INT,
                            'usersOn' => Connection::PARAM_INT_ARRAY,
                            'userAllIds' => Connection::PARAM_INT_ARRAY
                        ];

                        $whereCountry = 'and pr.country = :country';
                        $whereLocation = '';

                        if (Port::COUNTRY_US == $port->getCountry()) {
                            if ($port->getRegionId() and 1 != $port->getRegionId()) {
                                $whereLocation = 'and pr.region_id = :regionId';
                            }
                        }

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
                                WHERE pr.active = 1
                                AND pr.dead = 0
                                $whereCountry $whereLocation
                                AND pools is null                                
                                and pr.id IN (SELECT proxy_ip FROM user_ports WHERE user_id IN (:usersOn))
                                and pr.id NOT IN (SELECT proxy_ip FROM user_ports WHERE category != :categorySneaker AND user_id NOT IN (:usersOn))
                                and pr.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id IN (:userAllIds))
                                and pr.id NOT IN (SELECT proxy_id FROM user_ports_frozen)
                                and pr.id NOT IN (SELECT previous_proxy_ip FROM user_ports WHERE user_type = :userType and user_id IN (:userAllIds))
                                AND pr.id NOT IN (SELECT proxy_id FROM proxy_user_history WHERE user_type = :userType and user_id IN (:userAllIds))
                                GROUP BY pr.id
                                HAVING count = 1
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
                            $this->log("Found dedi id $result by \"special-generator\" logic case for " . $port->getUserId() . " user",
                                ['rule' => $rule]);
                        }

                        return $result;
                    },

                    // Should it continue to use common logic if not found
                    function(Port $port) use ($getRules) {
                        $rule = $getRules($port);

                        // Something wrong
                        if (!$rule) {
                            return false;
                        }

                        return $rule['backup'];
                    },
                ];
            })
        ];

        foreach ($handlers as $i => $handler) {
            if ($handler instanceof SpecialCustomerRuleBuilder) {
                $handlers[ $i ] = $handler->build();
                if ($this->logger) {
                    $handler->setLogger($this->logger);
                }
            }
        }

        return $handlers;
    }
}
