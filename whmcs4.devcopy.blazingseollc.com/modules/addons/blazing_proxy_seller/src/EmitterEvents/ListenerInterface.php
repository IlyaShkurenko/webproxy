<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents;

interface ListenerInterface
{

    /**
     * @return array
     */
    public function getEmitterListenedEvents();

    public function handleEmitterEvent(AbstractEvent $e);
}
