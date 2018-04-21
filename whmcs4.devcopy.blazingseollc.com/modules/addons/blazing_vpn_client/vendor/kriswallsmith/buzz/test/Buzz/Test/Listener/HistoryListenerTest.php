<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Test\Listener;

use Blazing\Vpn\Client\Vendor\Buzz\Listener\HistoryListener;
use Blazing\Vpn\Client\Vendor\Buzz\Message;
class HistoryListenerTest extends \PHPUnit_Framework_TestCase
{
    private $journal;
    private $listener;
    protected function setUp()
    {
        $this->journal = $this->getMockBuilder('Blazing\\Vpn\\Client\\Vendor\\Buzz\\Listener\\History\\Journal')->disableOriginalConstructor()->getMock();
        $this->listener = new HistoryListener($this->journal);
    }
    public function testHistory()
    {
        $request = new Message\Request();
        $response = new Message\Response();
        $this->journal->expects($this->once())->method('record')->with($request, $response, $this->isType('float'));
        $this->listener->preSend($request);
        $this->listener->postSend($request, $response);
    }
    public function testGetter()
    {
        $this->assertSame($this->journal, $this->listener->getJournal());
    }
}