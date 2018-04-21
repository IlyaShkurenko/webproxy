<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Listener;

use Blazing\Vpn\Client\Vendor\Buzz\Message\MessageInterface;
use Blazing\Vpn\Client\Vendor\Buzz\Message\RequestInterface;
class ListenerChain implements ListenerInterface
{
    /** @var ListenerInterface[] */
    private $listeners;
    public function __construct(array $listeners = array())
    {
        $this->listeners = $listeners;
    }
    public function addListener(ListenerInterface $listener)
    {
        $this->listeners[] = $listener;
    }
    public function getListeners()
    {
        return $this->listeners;
    }
    public function preSend(RequestInterface $request)
    {
        foreach ($this->listeners as $listener) {
            $listener->preSend($request);
        }
    }
    public function postSend(RequestInterface $request, MessageInterface $response)
    {
        foreach ($this->listeners as $listener) {
            $listener->postSend($request, $response);
        }
    }
}