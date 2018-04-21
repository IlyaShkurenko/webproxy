<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Test\Listener;

use Blazing\Vpn\Client\Vendor\Buzz\Listener\ListenerChain;
use Blazing\Vpn\Client\Vendor\Buzz\Message;
class ListenerChainTest extends \PHPUnit_Framework_TestCase
{
    public function testListeners()
    {
        $listener = new ListenerChain(array($this->getMockBuilder('Blazing\\Vpn\\Client\\Vendor\\Buzz\\Listener\\ListenerInterface')->getMock()));
        $this->assertEquals(1, count($listener->getListeners()));
        $listener->addListener($this->getMockBuilder('Blazing\\Vpn\\Client\\Vendor\\Buzz\\Listener\\ListenerInterface')->getMock());
        $this->assertEquals(2, count($listener->getListeners()));
    }
    public function testChain()
    {
        $delegate = $this->getMockBuilder('Blazing\\Vpn\\Client\\Vendor\\Buzz\\Listener\\ListenerInterface')->getMock();
        $request = new Message\Request();
        $response = new Message\Response();
        $delegate->expects($this->once())->method('preSend')->with($request);
        $delegate->expects($this->once())->method('postSend')->with($request, $response);
        $listener = new ListenerChain(array($delegate));
        $listener->preSend($request);
        $listener->postSend($request, $response);
    }
}