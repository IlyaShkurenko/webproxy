<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Test\Message;

use Blazing\Vpn\Client\Vendor\Buzz\Message\Factory\Factory;
use Blazing\Vpn\Client\Vendor\Buzz\Message\Request;
use Blazing\Vpn\Client\Vendor\Buzz\Message\RequestInterface;
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    private $factory;
    protected function setUp()
    {
        $this->factory = new Factory();
    }
    public function testCreateRequestDefaults()
    {
        $request = $this->factory->createRequest();
        $this->assertInstanceOf('Blazing\\Vpn\\Client\\Vendor\\Buzz\\Message\\Request', $request);
        $this->assertEquals(RequestInterface::METHOD_GET, $request->getMethod());
        $this->assertEquals('/', $request->getResource());
        $this->assertNull($request->getHost());
    }
    public function testCreateRequestArguments()
    {
        $request = $this->factory->createRequest(RequestInterface::METHOD_POST, '/foo', 'http://example.com');
        $this->assertEquals(RequestInterface::METHOD_POST, $request->getMethod());
        $this->assertEquals('/foo', $request->getResource());
        $this->assertEquals('http://example.com', $request->getHost());
    }
    public function testCreateFormRequestDefaults()
    {
        $request = $this->factory->createFormRequest();
        $this->assertInstanceOf('Blazing\\Vpn\\Client\\Vendor\\Buzz\\Message\\Form\\FormRequest', $request);
        $this->assertEquals(RequestInterface::METHOD_POST, $request->getMethod());
        $this->assertEquals('/', $request->getResource());
        $this->assertNull($request->getHost());
    }
    public function testCreateFormRequestArguments()
    {
        $request = $this->factory->createFormRequest(RequestInterface::METHOD_PUT, '/foo', 'http://example.com');
        $this->assertInstanceOf('Blazing\\Vpn\\Client\\Vendor\\Buzz\\Message\\Form\\FormRequest', $request);
        $this->assertEquals(RequestInterface::METHOD_PUT, $request->getMethod());
        $this->assertEquals('/foo', $request->getResource());
        $this->assertEquals('http://example.com', $request->getHost());
    }
    public function testCreateResponse()
    {
        $this->assertInstanceOf('Blazing\\Vpn\\Client\\Vendor\\Buzz\\Message\\Response', $this->factory->createResponse());
    }
}