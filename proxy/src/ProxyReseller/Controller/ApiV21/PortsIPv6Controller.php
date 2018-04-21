<?php

namespace ProxyReseller\Controller\ApiV21;

use Application\Helper;
use ProxyReseller\Controller\AbstractVersionedController;

class PortsIPv6Controller extends AbstractVersionedController
{
    public function getAllAction($userId)
    {
        $user = $this->getUser($userId);

        $stmt = $this->getConn()->executeQuery("
                SELECT u.id as userId, u.login, u.email, u.api_key as apiKey, up.id,
                  p.id as packageId, p.ext, 
                  pi.block, pi.subnet, s.id as serverId, s.ip as serverIp
                FROM user_ports_ipv6 up
                INNER JOIN proxy_user_packages p ON p.id = up.package_id
                INNER JOIN proxy_users u ON u.id = p.user_id
                INNER JOIN proxies_ipv6 pi ON pi.id = up.block_id
                INNER JOIN proxies_ipv6_sources pis ON pis.id = pi.source_id
                INNER JOIN proxy_servers_ipv6 s ON s.id = pis.server_id
                WHERE u.id = ?
                ORDER BY p.id, s.id, up.id
            ", [$user['id']]);

        $dataset = [];
        $i = 1;
        $latest = true;
        $result = [];
        while ($row = $stmt->fetch() or ($latest and !($latest = false))) {
            /*if (!$builder) {
                $builder = new Ipv6ExportedPortsBuilder();
            }
            $port = (new ExportedPort())
                ->setUserId($row['userId'])!
                ->setLogin($row['login'])
                ->setApiKey($row['apiKey'])
                ->setPortId($row['id'])
                ->setPackageId($row['packageId'])
                ->setExt($row['ext'])
                ->setBlock($row['block'])
                ->setSubnet($row['subnet'])
                ->setServerId($row['serverId'])
                ->setServerIp($row['serverIp'])*/
            ;
            // New dataset, push the previous one
            if (!empty($dataset['userId']) and
                (
                    $dataset['userId'] != $row['userId'] or
                    $dataset['packageId'] != $row['packageId'] or
                    $dataset['serverId'] != $row['serverId']
                )) {

                foreach ($dataset['indexes'] as $i => $index) {
                    $result[] = [
                        'serverIp' => $dataset['serverIp'],
                        'serverPort' => 4444,
                        'login' => join('-', array_merge([$dataset['login']], array_values($index))),
                        'block' => $dataset['blocks'][$i]['block'],
                        'subnet' => $dataset['blocks'][$i]['subnet'],
                        'type' => $dataset['type'],
                    ];
                }

                // Clean up the previous dataset
                $dataset = [];
            }
            if (empty($dataset['userId'])) {
                $i = 1;
                $dataset['userId'] = $row['userId'];
                $dataset['packageId'] = $row['packageId'];
                $dataset['serverId'] = $row['serverId'];
                $dataset['serverIp'] = $row['serverIp'];
                $dataset['type'] = '56x2';
                $dataset['apiKey'] = $row['apiKey'];

                if (!$row[ 'login' ]) {
                    continue;
                }

                $login = array_filter(explode(',', $row[ 'login' ]));
                $login = end($login);
                $dataset[ 'login' ] = Helper::sanitizeLogin($login, $this->app);
            }
            $dataset['blocks'][] = ['block' => $row['block'], 'subnet' => $row['subnet']];
            $dataset['blocksIndexes'][$row['block']][] = true;
            $dataset['indexes'][] = [
                'block' => count($dataset['blocksIndexes']),
                'subblock' => count($dataset['blocksIndexes'][$row['block']])
            ];

            $i++;
        }

        return [
            'list' => array_values($result)
        ];
    }
}
