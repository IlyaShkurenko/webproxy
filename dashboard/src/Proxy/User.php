<?php

namespace Proxy;

use Axelarge\ArrayTools\Arr;
use Blazing\Reseller\Api\Api;
use Blazing\Common\RestApiRequestHandler\Exception\BadRequestException;
use Proxy\Util\TFA;
use Silex\Application;
use Symfony\Component\HttpFoundation\Session\Session;

class User
{

    protected $app;

    /**
     * @var Api
     */
    protected $api;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->api = $app['api'];

        // Adjust API context
        if ($this->isAuthorized()) {
            $this->api->getContext()->setUserId($this->getId());
        }
    }

    public function isAuthorized()
    {
        return !!$this->app[ 'session' ]->get('userId');
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->app['session'];
    }

    public function getId()
    {
        return (int) $this->app[ 'session' ]->get('userId');
    }

    public function authorizeById($userId)
    {
        $this->deauthorize();
        $this->app[ 'session' ]->set('userId', $userId);

        // Try to load data and decide if we authorized
        try {
            $this->getDetails();

            // Adjust API context
            $this->api->getContext()->setUserId($this->getId());

            // Adjust logs indexes
            if (!empty($this->app['logs'])) {
                $this->app['logs']->addSharedIndex('userId', $this->getId());
            }
        }
        catch (BadRequestException $e) {
            // User not found
            $this->deauthorize();

            // Just rethrow to exception to be seen$this->getUser()->authorizeUserId()
            throw $e;
        }

        return $this;
    }

    public function deauthorize()
    {
        $this->app[ 'session' ]->remove('userId');
        $this->refreshData();
    }

    public function refreshData()
    {
        $this->app[ 'session' ]->remove('userData');
    }

    public function getDetails($key = null)
    {
        if (!$this->isAuthorized()) {
            return $key ? false : [];
        }

        if (!$this->app[ 'session' ]->get('userData')) {
            $this->app['session']->set('userData', $this->api->user()->getDetails($this->getId()));
        }

        return !$key ?
            $this->app[ 'session' ]->get('userData') :
            Arr::getOrElse($this->app[ 'session' ]->get('userData'), $key);
    }

    /**
     * @return TFA|false
     */
    public function getTFA()
    {
        return !empty($this->app['session.tfa']) ? $this->app['session.tfa'] : false;
    }
}
