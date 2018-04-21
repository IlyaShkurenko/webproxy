<?php

namespace ProxyReseller\Controller\ApiV21;

use ProxyReseller\Controller\AbstractVersionedController;
use ProxyReseller\Exception\ApiException;

class MtaController extends AbstractVersionedController
{
    public function isUserWhitelisted($userKey)
    {
        // Validate parameters
        $this->getAccount();
        $this->assertOrException(trim($userKey), "userKey cannnot be empty");

        $sql = 'SELECT id FROM user_mta_exclude WHERE ';
        $sqlArgs = [];

        // user id
        if (ctype_digit($userKey)) {
            $user = $this->getUser($userKey);
            $sql .= 'user_id = ?';
            $sqlArgs[] = $user['id'];
        }
        else {
            return [
                'status' => 'ok',
                'result' => false
            ];
            // $sql .= 'user_key = ?';
            // $sqlArgs[] = $userKey;
        }

        $exists = $this->getConn()->executeQuery($sql, $sqlArgs)->fetchColumn();

        return [
            'status' => 'ok',
            'result' => !!$exists
        ];
    }

    public function isIpTrusted($userKey, $ip)
    {
        // Validate parameters
        $this->getAccount();
        $this->assertOrException(trim($userKey), "userKey cannnot be empty");

        $sql = 'SELECT id FROM user_mta_ips WHERE ip = ? AND ';
        $sqlArgs = [$ip];

        if (ctype_digit($userKey)) {
            $user = $this->getUser($userKey);
            $sql .= 'user_id = ?';
            $sqlArgs[] = $user['id'];
        }
        else {
            $sql .= 'user_key = ?';
            $sqlArgs[] = $userKey;
        }

        $exists = $this->getConn()->executeQuery($sql, $sqlArgs)->fetchColumn();

        return [
            'status' => 'ok',
            'result' => !!$exists
        ];
    }

    public function upsertUserIp($userKey, $ip)
    {
        // Also the parameters are being validated by isIpTrusted method
        $exists = $this->isIpTrusted($userKey, $ip)['result'];

        if (!$exists) {
            $this->getConn()->insert('user_mta_ips', array_merge(ctype_digit($userKey) ?
                ['user_id' => $this->getUser($userKey)['id']] : ['user_key' => $userKey],
                [
                    'ip'           => $ip,
                    'date_created' => date('Y-m-d H:i:s'),
                    'date_checked' => date('Y-m-d H:i:s'),
                ]));
        }
        else {
            $this->getConn()->update('user_mta_ips', ['date_checked' => date('Y-m-d H:i:s')], array_merge(ctype_digit($userKey) ?
                ['user_id' => $userKey] : ['user_key' => $userKey],
                [
                    'ip' => $ip,
                ]));
        }

        return [
            'status' => 'ok',
            'created' => !$exists,
            'updated' => $exists,
        ];
    }

    public function deleteUserIp($userKey, $ip)
    {
        $deleted = $this->getConn()->delete('user_mta_ips', array_merge(ctype_digit($userKey) ?
            ['user_id' => $userKey] : ['user_key' => $userKey],
            [
                'ip' => $ip,
            ]));

        return [
            'status' => 'ok',
            'deleted' => !!$deleted,
        ];
    }

    public function deleteAllUserIps($userKey)
    {
        $deleted = $this->getConn()->delete('user_mta_ips', ctype_digit($userKey) ?
            ['user_id' => $userKey] : ['user_key' => $userKey]
        );

        return [
            'status' => 'ok',
            'deleted' => !!$deleted,
        ];
    }

    public function storeOtp($userKey, $type, $code, $lifetime, $attempts)
    {
        // Validate parameters
        $account = $this->getAccount();
        $this->assertOrException(trim($userKey), "userKey cannnot be empty");

        $expiration = date('Y-m-d H:i:s', time() + $lifetime);

        $this->getConn()->insert('user_mta_otp', array_merge(ctype_digit($userKey) ?
            ['user_id' => $this->getUser($userKey)['id']] : ['user_key' => $userKey, 'reseller_id' => $account['id']],
            [
                'type'       => $type,
                'code'       => $code,
                'expiration' => $expiration,
                'attempts'   => $attempts
            ]));

        return [
            'status' => 'ok',
            'expiration' => $expiration
        ];
    }

    public function isOtpGenerated($userKey, $type)
    {
        try {
            $result = $this->getOtp($userKey, $type, false);

            return [
                'status' => 'ok',
                'result' => true,
                'otp' => $result['otp']
            ];
        }
        catch (ApiException $e) {
            return [
                'status' => 'ok',
                'result' => false
            ];
        }
    }

    public function getOtp($userKey, $type, $hasAttempts = true, $code = false, $notExpired = true)
    {
        // Validate parameters
        $account = $this->getAccount();
        $this->assertOrException(trim($userKey), "userKey cannnot be empty");

        $parameters = array_merge(ctype_digit($userKey) ?
            ['user_id' => $this->getUser($userKey)['id']] : ['user_key' => $userKey, 'reseller_id' => $account['id']],
            [
                'type'    => $type
            ]);

        if ($hasAttempts) {
            $parameters['attempts'] = ['>', 0];
        }
        if ($code) {
            $parameters['code'] = $code;
        }
        if ($notExpired) {
            $parameters['expiration'] = ['>=', date('Y-m-d H:i:s')];
        }

        $where = [
            'selectors' => [],
            'values' => []
        ];
        foreach ($parameters as $key => $param) {
            if (is_array($param)) {
                $where[ 'selectors' ][] = "`{$key}` {$param[ 0 ]} ?";
                $where[ 'values' ][] = $param[ 1 ];
            }
            else {
                $where[ 'selectors' ][] = "`{$key}` = ?";
                $where[ 'values' ][] = $param;
            }
        }

        $result = $this->getConn()->executeQuery('
          SELECT `type`, code, attempts, expiration
          FROM user_mta_otp
          WHERE ' . join(' AND ', $where['selectors']) . ' ' .
            'ORDER BY id DESC',
            $where['values'])
            ->fetch();

        $this->assertOrException($result, 'OTP doesn\'t exist', [], 'NOT_EXIST');

        return [
            'status' => 'ok',
            'otp' => $result
        ];
    }

    public function decrementOtpAttempts($userKey, $type)
    {
        // Validate parameters
        $account = $this->getAccount();
        $this->assertOrException(trim($userKey), "userKey cannnot be empty");

        $sql = 'UPDATE user_mta_otp
              SET attempts = attempts - 1
              WHERE `type` = ? AND attempts > 0 AND expiration >= ? AND ';
        $sqlArgs = [$type, date('Y-m-d H:i:s')];

        if (ctype_digit($userKey)) {
            $user = $this->getUser($userKey);
            $sql .= 'user_id = ?';
            $sqlArgs[] = $user['id'];
        }
        else {
            $sql .= 'user_key = ? AND reseller_id = ?';
            $sqlArgs[] = $userKey;
            $sqlArgs[] = $account['id'];
        }

        $affected = $this->getConn()->executeUpdate($sql, $sqlArgs);

        return [
            'status'      => 'ok',
            'updatedOtps' => $affected,
            'otp' => $affected ? $this->getOtp($userKey, $type, false)['otp'] : false
        ];
    }

    public function deleteOtp($userKey, $type)
    {
        // Validate parameters
        $account = $this->getAccount();
        $this->assertOrException(trim($userKey), "userKey cannnot be empty");

        $result = $this->getConn()->delete('user_mta_otp', array_merge(ctype_digit($userKey) ?
            ['user_id' => $this->getUser($userKey)['id']] : ['user_key' => $userKey, 'reseller_id' => $account['id']],
            [
                'type'    => $type
            ]));

        return [
            'status'  => 'ok',
            'removedOtps' => $result
        ];
    }
}
