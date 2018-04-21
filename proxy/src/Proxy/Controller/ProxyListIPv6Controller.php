<?php

namespace Proxy\Controller;

use Application\AbstractApiController;
use Application\Helper;
use Proxy\DataBuilder\Ipv6ExportedPortsBuilder;
use Proxy\Model\ExportedPort;
use Symfony\Component\HttpFoundation\Response;

class ProxyListIPv6Controller extends AbstractApiController
{
    protected $convertResponse = 'text-rows';

    protected function onControllerExceptionResponse(Response $response)
    {
        $response->setStatusCode(400);
    }

    public function getBlocksAllocationAction($ip)
    {
        if ('all' == $ip) {
            $ip = false;
        }

        $where = '1 = 1';
        $parameters = [];

        if ($ip) {
            $this->assertOrException(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4), 'Bad IP');
            $where .= ' AND s.ip = :ip';
            $parameters['ip'] = $ip;
        }

        $rows = $this->getConn()->executeQuery("
            SELECT b.block, b.subnet, s.ip as serverIp
            FROM proxies_ipv6_sources b
            INNER JOIN proxy_servers_ipv6 s
            WHERE $where
        ", $parameters)->fetchAll();

        $this->assertOrException($rows, 'Bad request');

        return array_map(function($row) use ($ip) {
            return ($ip ? '' : "{$row['serverIp']}:") . "{$row['block']}/{$row['subnet']}";
        }, $rows);
    }

    public function getUserPackagesAction($ip)
    {
        if ('all' == $ip) {
            $ip = false;
        }

        $where = '1 = 1';
        $parameters = [];
        if ($ip) {
            $this->assertOrException(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4), 'Bad IP');
            $where .= ' AND s.ip = :ip';
            $parameters['ip'] = $ip;
        }

        return $this->getTextPlainStreamResponse(function() use ($where, $parameters) {
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
                WHERE $where
                ORDER BY p.id, p.user_id, s.id, up.id
            ", $parameters);

            $dataset = [];
            $i = 1;
            $latest = true;
            while ($row = $stmt->fetch() or ($latest and !($latest = false))) {
                // New dataset, push the previous one
                if (!empty($dataset['userId']) and
                    (
                        $dataset['userId'] != $row['userId'] or
                        $dataset['packageId'] != $row['packageId'] or
                        $dataset['serverId'] != $row['serverId']
                    )) {
                    echo join(',', array_merge(
                        [$dataset['login'], $dataset['apiKey'], $dataset['type']],
                            array_unique(array_map(
                                function($data) { return "{$data['block']}/{$data['subnet']}"; },
                                $dataset['blocks'])
                            ))) .
                        PHP_EOL;

                    foreach ($dataset['indexes'] as $index) {
                        echo join(':', [$dataset['serverIp'], 4444, join('-', array_merge([$dataset['login']], array_values($index)))]) .
                        PHP_EOL;
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
        });
    }
}
