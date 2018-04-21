<?php

namespace WHMCS\Module\Blazing\Proxy\Seller;

use InvalidArgumentException;
use League\Event\Emitter as BaseEmitter;
use WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents\AbstractEvent;
use WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents\ListenerInterface;
use WHMCS\Module\Framework\Events\AbstractListener;

class Emitter extends BaseEmitter
{
    public static function getInstance()
    {
        static $instance;
        if (!$instance) {
            $instance = new static();
        }

        return $instance;
    }

    public static function emitEvent($evt)
    {
        return static::getInstance()->emit($evt);
    }

    protected function __construct()
    {
        foreach (EmitterListeners::LISTENERS as $listener) {
            if (!is_subclass_of($listener, ListenerInterface::class)) {
                throw new InvalidArgumentException('Listeners should be ListenerInterface. Received type: '.gettype($listener));
            }

            if (is_subclass_of($listener, AbstractListener::class)) {
                /** @var AbstractListener $listener */
                $listener = $listener::getInstance();
            }
            else {
                $listener = new $listener;
            }

            /** @var ListenerInterface $listener */
            $events = $listener->getEmitterListenedEvents();
            foreach ($events as $event) {
                /** @var AbstractEvent $event */
                $this->addListener($event::name(), function(AbstractEvent $evt) use ($listener) {
                    $listener->handleEmitterEvent($evt);
                });
            }
        }
    }
}
