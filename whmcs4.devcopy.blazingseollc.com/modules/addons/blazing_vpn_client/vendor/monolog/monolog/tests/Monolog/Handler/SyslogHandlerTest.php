<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Blazing\Vpn\Client\Vendor\Monolog\Handler;

use Blazing\Vpn\Client\Vendor\Monolog\Logger;
class SyslogHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Monolog\Handler\SyslogHandler::__construct
     */
    public function testConstruct()
    {
        $handler = new SyslogHandler('test');
        $this->assertInstanceOf('Blazing\\Vpn\\Client\\Vendor\\Monolog\\Handler\\SyslogHandler', $handler);
        $handler = new SyslogHandler('test', LOG_USER);
        $this->assertInstanceOf('Blazing\\Vpn\\Client\\Vendor\\Monolog\\Handler\\SyslogHandler', $handler);
        $handler = new SyslogHandler('test', 'user');
        $this->assertInstanceOf('Blazing\\Vpn\\Client\\Vendor\\Monolog\\Handler\\SyslogHandler', $handler);
        $handler = new SyslogHandler('test', LOG_USER, Logger::DEBUG, true, LOG_PERROR);
        $this->assertInstanceOf('Blazing\\Vpn\\Client\\Vendor\\Monolog\\Handler\\SyslogHandler', $handler);
    }
    /**
     * @covers Monolog\Handler\SyslogHandler::__construct
     */
    public function testConstructInvalidFacility()
    {
        $this->setExpectedException('UnexpectedValueException');
        $handler = new SyslogHandler('test', 'unknown');
    }
}