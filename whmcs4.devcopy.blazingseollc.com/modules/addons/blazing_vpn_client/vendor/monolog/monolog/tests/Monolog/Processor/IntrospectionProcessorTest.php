<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Blazing\Vpn\Client\Vendor\Acme;

class Tester
{
    public function test($handler, $record)
    {
        $handler->handle($record);
    }
}
function tester($handler, $record)
{
    $handler->handle($record);
}
namespace Blazing\Vpn\Client\Vendor\Monolog\Processor;

use Blazing\Vpn\Client\Vendor\Monolog\Logger;
use Blazing\Vpn\Client\Vendor\Monolog\TestCase;
use Blazing\Vpn\Client\Vendor\Monolog\Handler\TestHandler;
class IntrospectionProcessorTest extends TestCase
{
    public function getHandler()
    {
        $processor = new IntrospectionProcessor();
        $handler = new TestHandler();
        $handler->pushProcessor($processor);
        return $handler;
    }
    public function testProcessorFromClass()
    {
        $handler = $this->getHandler();
        $tester = new \Blazing\Vpn\Client\Vendor\Acme\Tester();
        $tester->test($handler, $this->getRecord());
        list($record) = $handler->getRecords();
        $this->assertEquals(__FILE__, $record['extra']['file']);
        $this->assertEquals(18, $record['extra']['line']);
        $this->assertEquals('Blazing\\Vpn\\Client\\Vendor\\Acme\\Tester', $record['extra']['class']);
        $this->assertEquals('test', $record['extra']['function']);
    }
    public function testProcessorFromFunc()
    {
        $handler = $this->getHandler();
        \Blazing\Vpn\Client\Vendor\Acme\tester($handler, $this->getRecord());
        list($record) = $handler->getRecords();
        $this->assertEquals(__FILE__, $record['extra']['file']);
        $this->assertEquals(24, $record['extra']['line']);
        $this->assertEquals(null, $record['extra']['class']);
        $this->assertEquals('Blazing\\Vpn\\Client\\Vendor\\Acme\\tester', $record['extra']['function']);
    }
    public function testLevelTooLow()
    {
        $input = array('level' => Logger::DEBUG, 'extra' => array());
        $expected = $input;
        $processor = new IntrospectionProcessor(Logger::CRITICAL);
        $actual = $processor($input);
        $this->assertEquals($expected, $actual);
    }
    public function testLevelEqual()
    {
        $input = array('level' => Logger::CRITICAL, 'extra' => array());
        $expected = $input;
        $expected['extra'] = array('file' => null, 'line' => null, 'class' => 'ReflectionMethod', 'function' => 'invokeArgs');
        $processor = new IntrospectionProcessor(Logger::CRITICAL);
        $actual = $processor($input);
        $this->assertEquals($expected, $actual);
    }
    public function testLevelHigher()
    {
        $input = array('level' => Logger::EMERGENCY, 'extra' => array());
        $expected = $input;
        $expected['extra'] = array('file' => null, 'line' => null, 'class' => 'ReflectionMethod', 'function' => 'invokeArgs');
        $processor = new IntrospectionProcessor(Logger::CRITICAL);
        $actual = $processor($input);
        $this->assertEquals($expected, $actual);
    }
}