<?php

namespace Blazing\Vpn\Client\Vendor\Buzz\Test\Client;

use Blazing\Vpn\Client\Vendor\Buzz\Message;
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideInvalidHosts
     */
    public function testSendToInvalidUrl($host, $client)
    {
        $this->setExpectedException('Blazing\\Vpn\\Client\\Vendor\\Buzz\\Exception\\ClientException');
        $request = new Message\Request();
        $request->fromUrl('http://' . $host . ':12345');
        $response = new Message\Response();
        $client = new $client();
        $client->setTimeout(0.05);
        $client->send($request, $response);
    }
    public function provideInvalidHosts()
    {
        return array(array('invalid_domain', 'Blazing\\Vpn\\Client\\Vendor\\Buzz\\Client\\Curl'), array('invalid_domain.buzz', 'Blazing\\Vpn\\Client\\Vendor\\Buzz\\Client\\Curl'), array('invalid_domain', 'Blazing\\Vpn\\Client\\Vendor\\Buzz\\Client\\FileGetContents'), array('invalid_domain.buzz', 'Blazing\\Vpn\\Client\\Vendor\\Buzz\\Client\\FileGetContents'));
    }
}