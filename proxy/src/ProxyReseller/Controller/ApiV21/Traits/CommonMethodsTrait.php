<?php

namespace ProxyReseller\Controller\ApiV21\Traits;

use ProxyReseller\Controller\ApiV20\Traits\CommonMethodsTrait as BaseTrait;
use Proxy\Assignment\Port\IPv4;
use Proxy\Assignment\Port\IPv6;

trait CommonMethodsTrait
{
    use BaseTrait;

    protected function validateIpVersion($ipVersion, $canBeEmpty = false)
    {
        if (!$ipVersion and $canBeEmpty) {
            return;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertOrException(
            in_array($ipVersion, [IPv4\Port::INTERNET_PROTOCOL, IPv6\Package::INTERNET_PROTOCOL]),
            'ipVersion is invalid',
            ['ipVersion' => $ipVersion]
        );
    }
}
