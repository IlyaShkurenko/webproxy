<?php

namespace ProxyReseller\Controller\ApiV20;

use Application\Helper;
use ProxyReseller\Controller\AbstractVersionedController;
use ProxyReseller\Controller\ApiV20\Traits\CommonMethodsTrait;
use ProxyReseller\Exception\ApiException;

class UserManagementController extends AbstractVersionedController
{
    use CommonMethodsTrait;

    public function getUserIdByBillingAction($source, $id)
    {
        $this->validateBillingSource($source);
        $userId = $this->getConn()->executeQuery(
            "SELECT id FROM proxy_users WHERE reseller_id = ? AND {$source}_id != '' AND {$source}_id = ?",
            [$this->getAccount()['id'], $id]
        )->fetchColumn();
        $this->assertOrException($userId, 'Unable to find user', [], 'NO_USER_FOUND');

        return $this->getUserDetails($userId);
    }

    public function findUserByLoginOrEmail($loginOrEmail) {
        $userId = $this->getConn()->executeQuery("
            SELECT pu.id
            FROM proxy_users pu
            LEFT JOIN banditim_amember.am_user am ON pu.amember_id = am.user_id
            WHERE (pu.email = :key OR pu.login = :key OR am.login = :key) AND pu.reseller_id = :resellerId",
                ['key' => $loginOrEmail, 'resellerId' => $this->getAccount()['id']])
            ->fetchColumn();

        $this->assertOrException($userId, 'Unable to find user', [], 'NO_USER_FOUND', ApiException::LOG_INFO);

        return $this->getUserDetails($userId);
    }

    public function upsertUserAction($source, $id, $email, $password = false)
    {
        $this->assertOrException($source == 'whmcs', 'Billing type is invalid');
        $this->assertOrException(filter_var($email, FILTER_VALIDATE_EMAIL), 'E-mail is invalid', ['email' => $email]);

        $userById = $this->getConn()->fetchAssoc(
            "SELECT * FROM proxy_users WHERE reseller_id = ? AND {$source}_id = ?",
            [$this->getAccount()['id'], $id]);
        $userByEmail = $this->getConn()->fetchAssoc(
            "SELECT * FROM proxy_users WHERE reseller_id = ? AND email = ?", [$this->getAccount()['id'], $email]);

        // User is registered, email not changed
        if ($userById and $userByEmail) {
            if ($userById['id'] == $userByEmail['id']) {
                return $this->getUserDetails($userById['id']);
            }
            else {
                throw new ApiException("User with email {$userByEmail['email']} is already registered, " .
                    "but has another WHMCS account! Also your previous email was {$userById['email']}, " .
                    "try to change it back in you WHMCS account", [
                        'email' => [
                            'byId' => $userById['email'],
                            'given' => $email
                        ]
                ]);
            }
        }

        // Email changed, update it
        if ($userById and !$userByEmail) {
            $this->getConn()->update('proxy_users', ['email' => $email], ['id' => $userById['id']]);

            return $this->getUserDetails($userById['id']);
        }

        if (!$password) {
            $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        }

        // User has account, but it's not tied with whmcs account
        if (!$userById and $userByEmail) {
            // User with the same email cannot be registered
            if ($userByEmail["{$source}_id"] and $userByEmail["{$source}_id"] != $id) {
                throw new ApiException("User with email {$userByEmail['email']} is already registered, " .
                    "but has another " . strtoupper($source) . " account!");
            }

            $this->getConn()->update('proxy_users', ["{$source}_id" => $id], ['id' => $userByEmail['id']]);

            if (!$userByEmail['api_key']) {
                $this->getConn()->update('proxy_users', ['api_key' => $password], ['id' => $userByEmail['id']]);
            }

            return $this->getUserDetails($userByEmail['id']);
        }

        // User not registered

        $this->getConn()->insert('proxy_users', [
            'reseller_id'      => $this->getAccount()[ 'id' ],
            "{$source}_id"     => $id,
            'email'            => $email,
            'login'            => Helper::generateLogin($email, $this->getAccount()[ 'id' ]),
            'api_key'          => $password,
            'date_created'     => date('Y-m-d H:i:s'),
            'sneaker_location' => 'LA'
        ]);

        $userById = $this->getUserIdByBillingAction($source, $id);
        $this->assertOrException(!empty($userById['userId']), 'No user found after upsert. But we have been noticed on this issue');

        return $this->getUserDetails($userById['userId']);
    }

    protected function getUserDetails($userId)
    {
        $controller = new UserController($this->app);
        $controller->setRequest($this->request);

        return $controller->getDetailsAction($userId);
    }
}