<?php

namespace ProxyReseller\Controller\ApiV21;

use Application\Helper;
use ProxyReseller\Controller\ApiV20\UserController as BaseController;

class UserController extends BaseController
{
    const IP_AUTH_TYPE_HTTP = 0;
    const IP_AUTH_TYPE_SOCKS = 1;

    const IP_AUTH_TYPE_NAMES = [
        self::IP_AUTH_TYPE_HTTP => 'HTTP',
        self::IP_AUTH_TYPE_SOCKS => 'SOCKS'
    ];

    public function updateSettingsAction($userId, array $data)
    {
        $user = $this->getUser($userId);

        $options = [];
        foreach ([
                     'rotation_type'    => 'authType',
                     'rotate_ever'      => 'rotate_ever',
                     'rotate_30'        => 'rotate_30',
                     'preferred_format' => 'authType',
                     'preferred_ip_auth_type' => 'ipAuthType'
                 ] as $field => $mapped) {
            if (isset($data[ $mapped ])) {
                if ('authType' === $mapped) {
                    $this->assertOrException(in_array($data[ $mapped ], ['IP', 'PW']),
                        "authType can be only: \"IP\" or \"PW\", \"{$data[ $mapped ]}\" passed");

                    $options['preferred_format_update'] = date('Y-m-d H:i:s');
                } else if ('ipAuthType' === $mapped) {
                    $ipAuthTypesMap = array_flip(self::IP_AUTH_TYPE_NAMES);
                    $data[ $mapped ] = isset($ipAuthTypesMap[$data[ $mapped ]]) ? $ipAuthTypesMap[$data[ $mapped ]] : 0;
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

    public function getDetailsAction($userId)
    {
        $fieldsList = [
            'id'               => 'userId',
            'email'            => 'email',
            'login'            => 'login',
            'rotate_30'        => 'rotate_30',
            'rotate_ever'      => 'rotate_ever',
            'preferred_format' => 'authType',
            'preferred_ip_auth_type' => 'ipAuthType',
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

        $details[ 'ipAuthType' ] = self::IP_AUTH_TYPE_NAMES[$details[ 'ipAuthType' ]];

        // No need to share that info with no reason
        if (!$details[ 'isAdmin' ]) {
            unset($details[ 'isAdmin' ]);
        }

        return $details;
    }
}
