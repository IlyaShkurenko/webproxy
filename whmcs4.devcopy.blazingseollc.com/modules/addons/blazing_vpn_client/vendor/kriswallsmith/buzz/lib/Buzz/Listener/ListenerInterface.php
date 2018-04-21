<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Listener;

use Blazing\Vpn\Client\Vendor\Buzz\Message\MessageInterface;
use Blazing\Vpn\Client\Vendor\Buzz\Message\RequestInterface;
interface ListenerInterface
{
    public function preSend(RequestInterface $request);
    public function postSend(RequestInterface $request, MessageInterface $response);
}