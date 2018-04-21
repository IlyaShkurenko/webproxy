<?php

namespace ProxyReseller\Controller;

use Application\AbstractApiController as BaseController;

class AbstractApiController extends BaseController
{

    protected $convertResponse = 'json';
    protected $account;

    protected function onControllerRequest(callable $controller)
    {
        parent::onControllerRequest($controller);

        // Add user index
        if ($this->logger) {
            if ($userId = $this->request->attributes->get('userId')) {
                $this->logger->addSharedIndex('userId', $userId);
            }
        }

        // Check auth
        $account = $this->getAccount(true);

        // Add more indexes
        if ($this->logger) {
            $this->logger->addSharedIndex('accountId', $account[ 'id' ]);
        }
    }

    // --- Auth/request specific methods

    protected function getApiKey($throwIfNotFound = true)
    {
        $apiKey = $this->request->headers->get('Auth-Token');

        if (!$apiKey) {
            if ($throwIfNotFound) {
                $this->assertOrException(false, 'No API Key passed');
            }
        }

        return $apiKey ? $apiKey : false;
    }

    /**
     * Get reseller account reseller or throw exception + write that to user log
     *
     * @param bool $throwIfNotFound
     * @return array|bool
     * @throws \ErrorException
     * @throws \Exception
     */
    protected function getAccount($throwIfNotFound = true)
    {
        if (!$this->account) {
            $apiKey = $this->getApiKey($throwIfNotFound);

            if (!$apiKey) {
                return false;
            }

            $account = $this->getConn()->executeQuery("SELECT * FROM resellers WHERE api_key = ?", [$apiKey])->fetch();

            if (!$account) {
                if ($throwIfNotFound) {
                    $this->assertOrException(false, 'Invalid API Key or no account is registered',
                        ['apiKey' => $apiKey]);
                }
                else {
                    return false;
                }
            }

            $this->account = $account;
        }

        return $this->account;
    }

    protected function getUser($userId, $throwIfNotFound = true)
    {
        if (!$account = $this->getAccount($throwIfNotFound)) {
            return false;
        }

        $user = $this->getConn()->executeQuery(
            "SELECT * FROM proxy_users WHERE reseller_id = ? AND id = ?",
            [$account[ 'id' ], $userId]
        )->fetch();
        if (!$user) {
            if ($throwIfNotFound) {
                $this->assertOrException(false, "No user \"$userId\" found", ['userId' => $userId]);
            }
            else {
                return false;
            }
        }

        return $user;
    }
}
