<?php

namespace Common\Events;

use Common\Events\Events\AbstractEvent;
use Doctrine\DBAL\Connection;
use Silex\Application;

abstract class AbstractListener
{

    protected $events = [];

    protected $app;

    protected $dbConnMap = [
        'default'    => 'proxy',
        'unbuffered' => 'proxy_unbuffered',
        'rs'         => 'reseller',
        'am'         => 'amember'
    ];

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return $this->events;
    }

    abstract public function handle(AbstractEvent $e);

    public function setApplication(Application $application)
    {
        $this->app = $application;
    }

    // --- Helpers

    protected function getApp()
    {
        return $this->app;
    }

    /**
     * @param string $type
     * @return Connection
     */
    protected function getConn($type = '')
    {

        return $this->getApp()[ 'dbs' ][ !empty($this->dbConnMap[ $type ]) ?
            $this->dbConnMap[ $type ] :
            $this->dbConnMap[ 'default' ]
        ];
    }
}
