<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Message\Factory;

use Blazing\Vpn\Client\Vendor\Buzz\Message\RequestInterface;
interface FactoryInterface
{
    public function createRequest($method = RequestInterface::METHOD_GET, $resource = '/', $host = null);
    public function createFormRequest($method = RequestInterface::METHOD_POST, $resource = '/', $host = null);
    public function createResponse();
}