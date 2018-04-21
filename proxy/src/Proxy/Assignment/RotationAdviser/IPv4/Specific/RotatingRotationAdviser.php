<?php

namespace Proxy\Assignment\RotationAdviser\IPv4\Specific;

use Doctrine\DBAL\Driver\Statement;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\Assignment\RotationAdviser\AbstractParentDependentRotationAdviser;

class RotatingRotationAdviser extends AbstractParentDependentRotationAdviser
{
    private $limits = [];

    public function findRandomProxy(Port $port)
    {
        $key = join('.', [$port->getCountry(), $port->getUserType(), $port->getUserId()]);

        if (!isset($this->limits[ $key ])) {
            $userQuery = "SELECT count(*) as ports FROM user_ports 
              WHERE user_type = ? and user_id = ? and country = ? and category = ?";
            $ports = $this->conn->executeQuery($userQuery, [
                $port->getUserType(),
                $port->getUserId(),
                $port->getCountry(),
                Port::toOldCategory(Port::CATEGORY_ROTATING)
            ])->fetchColumn();

            $this->limits[ $key ][ 'limit' ] = $ports * $this->baseAdviser->getFromConfig('rules.rotating.rotateMultiple');

//            $totalQuery = "SELECT count(*) as pcount
//                FROM user_proxy_stats
//                WHERE proxy_id IN (SELECT id FROM proxies_ipv4 WHERE country = ?)
//                AND user_type = ? AND user_id = ?";
//            $count = $this->conn->executeQuery($totalQuery, [
//                    $port->getCountry(),
//                    $port->getUserType(),
//                    $port->getUserId()
//                ]
//            )->fetchColumn();
//            \benchPoint('count1 counter');
//
//            $this->limits[ $key ]['count'] = $count;

        }
//        elseif ($this->limits[ $key ][ 'count' ] < $this->limits[ $key ][ 'limit' ]) {
//            $totalQuery = "SELECT count(*) as pcount
//                FROM user_proxy_stats
//                WHERE proxy_id IN (SELECT id FROM proxies_ipv4 WHERE country = ?)
//                AND user_type = ? AND user_id = ?";
//            $count = $this->conn->executeQuery($totalQuery, [
//                    $port->getCountry(),
//                    $port->getUserType(),
//                    $port->getUserId()
//                ]
//            )->fetchColumn();
//            \benchPoint('count2 counter');
//
//            $this->limits[ $key ][ 'count' ] = $count;
//        }
//
//        \benchPoint('after counters');

        $limit = $this->limits[ $key ]['limit'];

//        $count = $this->limits[ $key ]['count'];
//
//        if ($count < $limit) {
//            $query = "SELECT id
//                FROM proxies_ipv4
//                WHERE active = 1 AND dead = 0 AND static = 0
//                AND country = :country
//                AND id NOT IN (SELECT proxy_ip FROM user_ports WHERE category != 'kushang')
//                ORDER BY RAND()
//                LIMIT 100
//            ";
//
//            return $this->getCachedQueryResult($query, [
//                'country' => $port->getCountry()
//            ], [], function(Statement $stmt) {
//                return $stmt->fetchColumn();
//            });
//        }

        $queryArgs = [
            'country' => $port->getCountry(),
            'userType' => $port->getUserType(),
            'userId' => $port->getUserId(),
            'categoryRotating' => Port::toOldCategory(Port::CATEGORY_ROTATING),
            'perIp' => $this->baseAdviser->getRule('rotating.perIp', $port),
            'limit' => $limit
        ];
        $queryTypes = ['limit' => \PDO::PARAM_INT, 'perIp' => \PDO::PARAM_INT];
        if ($perIpCountries = $this->baseAdviser->getRule('rotating.perIpCountries', $port)) {
            if (!empty($perIpCountries[$port->getCountry()])) {
                $queryArgs['perIp'] = $perIpCountries[$port->getCountry()];
            }
        }

        // Double up customers proxies
        if ($this->baseAdviser->getFromConfig('rules.rotating.onTopUsers.enabled') and
            in_array($port->getUserId(), $this->baseAdviser->getFromConfig('rules.dedicated.onTopUsers.users'))) {
            $sql = "
            SELECT DISTINCT id FROM (
                SELECT id, NOW() as sort_time
                FROM (
                    SELECT id
                    FROM proxies_ipv4
                    WHERE active = 1
                    AND dead = 0
                    AND static = 0
                    AND country = :country
                    AND id IN (SELECT `proxy_ip` FROM `user_ports` WHERE category = :categoryRotating)
                    and id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_type = :userType and user_id = :userId)
                    ORDER BY rand()
                    LIMIT :limit
                ) as prox
    
                ORDER BY sort_time ASC
                LIMIT :limit
            ) t";

            $result = $this->getCachedQueryResult($sql, $queryArgs, $queryTypes, function (Statement $stmt) {
                return $stmt->fetchColumn();
            });

            if ($result) {
                $this->log("Found rotating id $result by \"on top users\" logic case");

                return $result;
            }
        }

        $sql = "
            SELECT DISTINCT id FROM (
                SELECT p.id, ups.last_assigned as sort_time
                FROM (
                    SELECT *
                    FROM user_proxy_stats
                    WHERE proxy_id IN (SELECT id FROM proxies_ipv4 WHERE country = :country)
                    and user_type = :userType
                    and user_id = :userId
                    ORDER BY last_assigned DESC
                    LIMIT :limit
                ) as ups
                INNER JOIN proxies_ipv4 p ON ups.proxy_id = p.id
                LEFT JOIN user_ports up ON up.proxy_ip = p.id
                WHERE p.active = 1 AND p.dead = 0 AND p.static = 0 AND p.country = :country
                  AND p.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_id = :userId AND user_type = :userType) 
                GROUP BY p.id
                HAVING COUNT(up.id) < :perIp
                
                UNION ALL
                
                SELECT id, NOW() as sort_time
                FROM (
                    SELECT p.id
                    FROM proxies_ipv4 p
                    LEFT JOIN user_ports up ON up.proxy_ip = p.id
                    WHERE p.active = 1 AND p.dead = 0 AND p.static = 0 AND p.country = :country
                      AND p.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_id = :userId AND user_type = :userType)
                    GROUP BY p.id
                    HAVING COUNT(up.id) < :perIp
                    ORDER BY rand()
                    LIMIT :limit
                ) as prox
    
                ORDER BY sort_time ASC
                LIMIT :limit
            ) t";

        $result = $this->getCachedQueryResultColumn($sql, $queryArgs, $queryTypes);

        if ($result) {
            $this->log("Found rotating id $result by \"common\" logic case");

            return $result;
        }

        return false;
    }
}
