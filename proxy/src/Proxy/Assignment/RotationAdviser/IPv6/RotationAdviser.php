<?php

namespace Proxy\Assignment\RotationAdviser\IPv6;

use Doctrine\DBAL\Driver\Statement;
use PDO;
use Proxy\Assignment\Port\IPv6\Port;
use Proxy\Assignment\RotationAdviser\AbstractRotationAdviser;

class RotationAdviser extends AbstractRotationAdviser
{

    protected function getNameClassConfig()
    {
        return 'assign_ipv6';
    }

    public function findDedicatedBlockId(Port $port)
    {
        $ext = $port->getParsedExt();
        if (empty($ext['perSubnet'])) {
            $this->log('perSubnet is not defined in package', ['port' => $port->toArray()], 'warn');

            return false;
        }
        if (empty($ext['subnet'])) {
            $this->log('subnet is not defined in package', ['port' => $port->toArray()], 'warn');

            return false;
        }

        $baseSubnet = 48;
        $targetSubnet = (int) $ext['subnet'];
        if ($targetSubnet < $baseSubnet) {
            $this->log('targetSubnet cannot be lesser than baseSubnet', [
                'baseSubnet' => $baseSubnet,
                'targetSubnet' => $targetSubnet,
                'port' => $port->toArray()
            ], 'warn');

            return false;
        }
        $maxIpsOnSubnet = pow(2, ($targetSubnet - $baseSubnet));

        $id = $this->conn->executeQuery('
            SELECT p.id
            FROM proxies_ipv6 p
            INNER JOIN user_ports_ipv6 up ON up.block_id = p.id
            INNER JOIN proxy_user_packages pup ON pup.id = up.package_id
            LEFT JOIN (
              SELECT up.block_id, COUNT(*) as count
              FROM user_ports_ipv6 up
              INNER JOIN proxy_user_packages pup ON pup.id = up.package_id
              WHERE pup.user_id != :userId AND pup.type = :packageType
              GROUP BY up.block_id              
            ) upo ON upo.block_id = p.id
            WHERE up.user_id = :userId AND (upo.count IS NULL OR upo.count < :perBlock)
            GROUP BY p.id
            HAVING COUNT(*) < :assignPerSubnet
            LIMIT 1
        ', [
            'userId'          => $port->getUserId(),
            'assignPerSubnet' => $ext[ 'perSubnet' ],
            'packageType'     => $port->getType(),
            'perBlock'        => $maxIpsOnSubnet
        ])->fetchColumn();

        if ($id) {
            $this->log("Found dedi block $id by \"same user block\" logic case");

            return $id;
        }

        $id = $this->getCachedQueryResult('
            SELECT p.id
            FROM proxies_ipv6 p
            LEFT JOIN (
              SELECT up.block_id, COUNT(*) as count
              FROM user_ports_ipv6 up
              INNER JOIN proxy_user_packages pup ON pup.id = up.package_id
              WHERE pup.user_id != :userId AND pup.type = :packageType
              GROUP BY up.block_id              
            ) up ON up.block_id = p.id
            WHERE IF(up.count IS NULL, 0, up.count) < :perBlock
            AND p.id NOT IN (SELECT block_id FROM user_ports_ipv6 WHERE user_id = :userId AND block_id IS NOT NULL)
            GROUP BY p.id
            ORDER BY IF(up.block_id IS NULL, 1, 0)
            LIMIT 100
        ', [
            'userId'      => $port->getUserId(),
            'packageType' => $port->getType(),
            'perBlock'    => $maxIpsOnSubnet
        ], ['perBlock' => PDO::PARAM_INT], function(Statement $stmt) {
            return $stmt->fetchColumn();
        });

        if ($id) {
            $this->log("Found dedi block $id by \"common\" logic case");

            return $id;
        }

        return false;
    }
}
