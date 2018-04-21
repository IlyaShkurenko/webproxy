<?php

namespace Common\Events;

use Common\Events\Events\AbstractEvent;
use InvalidArgumentException;
use League\Event\Emitter as BaseEmitter;
use RuntimeException;
use Silex\Application;

class Emitter extends BaseEmitter
{

    /** @var Application */
    protected $application;

    public static function getInstance()
    {
        static $instance;
        if (!$instance) {
            $instance = new static();
        }

        return $instance;
    }

    protected function __construct()
    {
        foreach (EmitterListeners::LISTENERS as $listener) {
            if (!is_subclass_of($listener, AbstractListener::class)) {
                throw new InvalidArgumentException('Listeners should be AbstractListener. Received type: ' . gettype($listener));
            }

            $listener = new $listener;

            /** @var AbstractListener $listener */
            foreach ($listener->getSubscribedEvents() as $event) {
                /** @var AbstractEvent $event */
                $this->addListener($event::name(), function (AbstractEvent $evt) use ($listener) {
                    $listener->setApplication($this->getApplication());
                    $listener->handle($evt);
                });
            }
        }
    }

    /**
     * Get application
     *
     * @return Application
     */
    public function getApplication()
    {
        if (!$this->application) {
            throw new RuntimeException('No application is defined');
        }

        return $this->application;
    }

    /**
     * Set application
     *
     * @param Application $application
     * @return $this
     */
    public function setApplication($application)
    {
        $this->application = $application;

        return $this;
    }
}
