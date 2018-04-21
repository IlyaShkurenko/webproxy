<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Api;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Helper;
abstract class AbstractRequest
{
    protected function response($method, array $args = [], $resultPath = '')
    {
        $data = Helper::api($method, $args);
        return new ArrayResult($data, $resultPath, $method, $args);
    }
}