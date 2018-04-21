<?php

namespace ProxyReseller\Controller\ApiV20;

use Application\Helper;
use Proxy\Assignment\Port\IPv4\Port;
use ProxyReseller\Controller\AbstractVersionedController;
use ProxyReseller\Controller\ApiV20\Traits\CommonMethodsTrait;

class UserController extends AbstractVersionedController
{
    use CommonMethodsTrait;

    public function getDetailsAction($userId)
    {
        $fieldsList = [
            'id'               => 'userId',
            'email'            => 'email',
            'login'            => 'login',
            'rotate_30'        => 'rotate_30',
            'rotate_ever'      => 'rotate_ever',
            'preferred_format' => 'authType',
            'api_key'          => 'apiKey',
            'sneaker_location' => 'sneakerLocation',
            'admin'            => 'isAdmin',

            // Billing IDs
            'whmcs_id'         => 'whmcsId',
            'amember_id'       => 'amemberId'
        ];

        $fields = [];
        foreach ($fieldsList as $field => $alias) {
            $fields [] = "$field AS $alias";
        }
        $fields = join(', ', $fields);

        $details = $this->getConn()->executeQuery(
            "SELECT $fields
            FROM proxy_users
            WHERE reseller_id = ? AND id = ?", [$this->getAccount()[ 'id' ], $userId])
            ->fetch();

        $this->assertOrException($details, "User \"$userId\" is not found!", ['userId' => $userId]);

        $login = explode(',', $details['login']);
        $details['login'] = end($login);
        $details['login'] = Helper::sanitizeLogin($details['login'], $this->app);

        // No need to share that info with no reason
        if (!$details[ 'isAdmin' ]) {
            unset($details[ 'isAdmin' ]);
        }

        return $details;
    }

    public function updateSettingsAction($userId, array $data)
    {
        $user = $this->getUser($userId);

        $options = [];
        foreach ([
            'rotation_type'    => 'authType',
            'rotate_ever'      => 'rotate_ever',
            'rotate_30'        => 'rotate_30',
            'preferred_format' => 'authType'
        ] as $field => $mapped) {
            if (isset($data[ $mapped ])) {
                if ('authType' == $mapped) {
                    $this->assertOrException(in_array($data[ $mapped ], ['IP', 'PW']),
                        "authType can be only: \"IP\" or \"PW\", \"{$data[ $mapped ]}\" passed");

                    $options['preferred_format_update'] = date('Y-m-d H:i:s');
                }

                $options[ $field ] = $data[ $mapped ];
            }
        }
        $this->assertOrException($options, "No valid options passed");

        $this->getConn()->update('proxy_users', $options, ['id' => $user[ 'id' ]]);

        return [
            'status' => 'ok'
        ];
    }

    public function updateSneakerLocationAction($userId, $location)
    {
        $user = $this->getUser($userId);

        $this->assertOrException(in_array($location, ['LA', 'NY']), 'Invalid Location Passed');
        $this->getConn()->update('proxy_users', ['sneaker_location' => $location], ['id' => $user[ 'id' ]]);

        return [
            'status' => 'ok'
        ];
    }

    public function getAuthIpListAction($userId)
    {
        $user = $this->getUser($userId);

        return [
            'list' => $this->getConn()->fetchAll(
                'SELECT id, ip FROM user_ips WHERE user_type = ? AND user_id = ?',
                [Port::TYPE_CLIENT, $user[ 'id' ]]
            )
        ];
    }

    public function addAuthIpAction($userId, $ip)
    {
        $user = $this->getUser($userId);
        $maxIPs = 3500;

        $this->assertOrException(
            filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) AND
            !in_array($ip, ['0.0.0.0', '255.255.255.255']),
            'Not a valid IP address, must be valid IPv4 address.'
        );
        $this->assertOrException(
            !preg_match('~^(10\.|172.16\>|192\.168\.)~', $ip),
            'It\'s not allowed to use this IP address'
        );
        $this->assertFalseOrException(
            $this->getConn()->executeQuery(
                "SELECT `id` FROM `user_ips` WHERE `ip` = ? AND `user_id` = ? AND `user_type` = ?",
                [$ip, $user[ 'id' ], Port::TYPE_CLIENT])
                ->fetchColumn(), "Duplicate IP address.");
        $this->assertOrException(
            $this->getConn()->executeQuery(
                'SELECT count(*) FROM user_ips WHERE user_id = ?', [$user[ 'id' ]])
                ->fetchColumn() < ($maxIPs + 1), 'You have reached a limit');

        $this->getConn()->insert('user_ips', [
            'user_id'   => $user[ 'id' ],
            'user_type' => Port::TYPE_CLIENT,
            'ip'        => $ip
        ]);

        $id = $this->getConn()->executeQuery(
            "SELECT `id` FROM `user_ips` WHERE `ip` = ? AND `user_id` = ? AND `user_type` = ?",
            [$ip, $user[ 'id' ], Port::TYPE_CLIENT])
            ->fetchColumn();

        return [
            'status' => 'ok',
            'ip'     => [
                'id' => $id,
                'ip' => $ip
            ]
        ];
    }

    public function deleteAuthIpAction($userId, $ipId)
    {
        $user = $this->getUser($userId);

        $this->getConn()->delete('user_ips', [
            'user_id'   => $user[ 'id' ],
            'user_type' => 'BL',
            'id'        => $ipId,
        ]);

        return [
            'status' => 'ok'
        ];
    }
}
