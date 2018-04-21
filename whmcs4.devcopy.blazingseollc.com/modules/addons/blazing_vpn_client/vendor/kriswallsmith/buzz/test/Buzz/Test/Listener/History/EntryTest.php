<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Test\History;

use Blazing\Vpn\Client\Vendor\Buzz\Listener\History\Entry;
use Blazing\Vpn\Client\Vendor\Buzz\Message;
class EntryTest extends \PHPUnit_Framework_TestCase
{
    public function testDuration()
    {
        $entry = new Entry(new Message\Request(), new Message\Response(), 123);
        $this->assertEquals(123, $entry->getDuration());
    }
}