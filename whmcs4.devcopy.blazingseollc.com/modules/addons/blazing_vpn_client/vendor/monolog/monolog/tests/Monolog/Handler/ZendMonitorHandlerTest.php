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

use Blazing\Vpn\Client\Vendor\Monolog\TestCase;
class ZendMonitorHandlerTest extends TestCase
{
    protected $zendMonitorHandler;
    public function setUp()
    {
        if (!function_exists('zend_monitor_custom_event')) {
            $this->markTestSkipped('ZendServer is not installed');
        }
    }
    /**
     * @covers  Monolog\Handler\ZendMonitorHandler::write
     */
    public function testWrite()
    {
        $record = $this->getRecord();
        $formatterResult = array('message' => $record['message']);
        $zendMonitor = $this->getMockBuilder('Blazing\\Vpn\\Client\\Vendor\\Monolog\\Handler\\ZendMonitorHandler')->setMethods(array('writeZendMonitorCustomEvent', 'getDefaultFormatter'))->getMock();
        $formatterMock = $this->getMockBuilder('Blazing\\Vpn\\Client\\Vendor\\Monolog\\Formatter\\NormalizerFormatter')->disableOriginalConstructor()->getMock();
        $formatterMock->expects($this->once())->method('format')->will($this->returnValue($formatterResult));
        $zendMonitor->expects($this->once())->method('getDefaultFormatter')->will($this->returnValue($formatterMock));
        $levelMap = $zendMonitor->getLevelMap();
        $zendMonitor->expects($this->once())->method('writeZendMonitorCustomEvent')->with($levelMap[$record['level']], $record['message'], $formatterResult);
        $zendMonitor->handle($record);
    }
    /**
     * @covers Monolog\Handler\ZendMonitorHandler::getDefaultFormatter
     */
    public function testGetDefaultFormatterReturnsNormalizerFormatter()
    {
        $zendMonitor = new ZendMonitorHandler();
        $this->assertInstanceOf('Blazing\\Vpn\\Client\\Vendor\\Monolog\\Formatter\\NormalizerFormatter', $zendMonitor->getDefaultFormatter());
    }
}