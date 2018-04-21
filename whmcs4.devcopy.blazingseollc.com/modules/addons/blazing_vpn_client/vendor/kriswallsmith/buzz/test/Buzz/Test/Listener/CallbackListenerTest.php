<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Test\Listener;

use Blazing\Vpn\Client\Vendor\Buzz\Listener\CallbackListener;
use Blazing\Vpn\Client\Vendor\Buzz\Message;
class CallbackListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testCallback()
    {
        $calls = array();
        $listener = new CallbackListener(function () use(&$calls) {
            $calls[] = func_get_args();
        });
        $request = new Message\Request();
        $response = new Message\Response();
        $listener->preSend($request);
        $listener->postSend($request, $response);
        $this->assertEquals(array(array($request), array($request, $response)), $calls);
    }
    public function testInvalidCallback()
    {
        $this->setExpectedException('Blazing\\Vpn\\Client\\Vendor\\Buzz\\Exception\\InvalidArgumentException');
        $listener = new CallbackListener(array(1, 2, 3));
    }
}