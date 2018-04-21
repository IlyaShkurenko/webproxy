<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Client;

use Blazing\Vpn\Client\Vendor\Buzz\Exception\ClientException;
use Blazing\Vpn\Client\Vendor\Buzz\Message\MessageInterface;
use Blazing\Vpn\Client\Vendor\Buzz\Message\RequestInterface;
interface ClientInterface
{
    /**
     * Populates the supplied response with the response for the supplied request.
     *
     * @param RequestInterface $request  A request object
     * @param MessageInterface $response A response object
     *
     * @throws ClientException If something goes wrong
     */
    public function send(RequestInterface $request, MessageInterface $response);
}