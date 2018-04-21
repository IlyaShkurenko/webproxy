<?php

namespace ProxyReseller\Controller\ApiV20;

use ProxyReseller\Controller\AbstractVersionedController;
use ProxyReseller\Controller\ApiV20\Traits\CommonMethodsTrait;
use ProxyReseller\Exception\ApiException;

class MtaController extends AbstractVersionedController
{
    use CommonMethodsTrait;

    public function isUserWhitelisted($userId)
    {
        // Validate userId
        $this->getUser($userId);

        $exists = $this->getConn()
            ->executeQuery('SELECT id FROM user_mta_exclude WHERE user_id = ?', [$userId])
            ->fetchColumn();

        return [
            'status' => 'ok',
            'result' => !!$exists
        ];
    }

    public function isIpTrusted($userId, $ip)
    {
        // Validate userId
        $this->getUser($userId);

        $exists = $this->getConn()
            ->executeQuery('SELECT id FROM user_mta_ips WHERE user_id = ? AND ip = ?', [$userId, $ip])
            ->fetchColumn();

        return [
            'status' => 'ok',
            'result' => !!$exists
        ];
    }

    public function upsertUserIp($userId, $ip)
    {
        // Validate userId
        $this->getUser($userId);

        $exists = !!$this->getConn()->executeQuery('SELECT id FROM user_mta_ips WHERE user_id = ? AND ip = ?',
            [$userId, $ip])->fetchColumn();

        if (!$exists) {
            $this->getConn()->insert('user_mta_ips', [
                'user_id'      => $userId,
                'ip'           => $ip,
                'date_created' => date('Y-m-d H:i:s'),
                'date_checked' => date('Y-m-d H:i:s'),
            ]);
        }
        else {
            $this->getConn()->update('user_mta_ips', ['date_checked' => date('Y-m-d H:i:s')], [
                'user_id' => $userId,
                'ip'      => $ip,
            ]);
        }

        return [
            'status' => 'ok',
            'created' => !$exists,
            'updated' => $exists,
        ];
    }

    public function storeOtp($userId, $type, $code, $lifetime, $attempts)
    {
        // Validate userId
        $this->getUser($userId);

        $expiration = date('Y-m-d H:i:s', time() + $lifetime);

        $this->getConn()->insert('user_mta_otp', [
            'type'       => $type,
            'user_id'    => $userId,
            'code'       => $code,
            'expiration' => $expiration,
            'attempts'   => $attempts
        ]);

        return [
            'status' => 'ok',
            'expiration' => $expiration
        ];
    }

    public function isOtpGenerated($userId, $type)
    {
        try {
            $result = $this->getOtp($userId, $type, false);

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

    public function getOtp($userId, $type, $hasAttempts = true, $code = false, $notExpired = true)
    {
        // Validate userId
        $this->getUser($userId);

        $parameters = [
            'user_id'    => $userId,
            'type'       => $type
        ];

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

    public function decrementOtpAttempts($userId, $type)
    {
        // Validate userId
        $this->getUser($userId);

        $affected = $this->getConn()->executeUpdate('
              UPDATE user_mta_otp 
              SET attempts = attempts - 1 
              WHERE `type` = ? AND user_id = ? AND attempts > 0 AND expiration >= ?',
            [$type, $userId, date('Y-m-d H:i:s')]);

        return [
            'status'      => 'ok',
            'updatedOtps' => $affected,
            'otp' => $affected ? $this->getOtp($userId, $type, false)['otp'] : false
        ];
    }

    public function deleteOtp($userId, $type)
    {
        // Validate userId
        $this->getUser($userId);

        $result = $this->getConn()->delete('user_mta_otp', ['user_id' => $userId, 'type' => $type]);

        return [
            'status'  => 'ok',
            'removedOtps' => $result
        ];
    }
}