<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Message\Factory;

use Blazing\Vpn\Client\Vendor\Buzz\Message\Form\FormRequest;
use Blazing\Vpn\Client\Vendor\Buzz\Message\Request;
use Blazing\Vpn\Client\Vendor\Buzz\Message\RequestInterface;
use Blazing\Vpn\Client\Vendor\Buzz\Message\Response;
class Factory implements FactoryInterface
{
    public function createRequest($method = RequestInterface::METHOD_GET, $resource = '/', $host = null)
    {
        return new Request($method, $resource, $host);
    }
    public function createFormRequest($method = RequestInterface::METHOD_POST, $resource = '/', $host = null)
    {
        return new FormRequest($method, $resource, $host);
    }
    public function createResponse()
    {
        return new Response();
    }
}