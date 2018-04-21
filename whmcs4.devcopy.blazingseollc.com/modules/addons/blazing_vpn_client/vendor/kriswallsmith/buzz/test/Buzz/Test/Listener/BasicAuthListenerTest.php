<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Test\Listener;

use Blazing\Vpn\Client\Vendor\Buzz\Listener\BasicAuthListener;
use Blazing\Vpn\Client\Vendor\Buzz\Message;
class BasicAuthListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicAuthHeader()
    {
        $request = new Message\Request();
        $this->assertEmpty($request->getHeader('Authorization'));
        $listener = new BasicAuthListener('foo', 'bar');
        $listener->preSend($request);
        $this->assertEquals('Basic ' . base64_encode('foo:bar'), $request->getHeader('Authorization'));
    }
}