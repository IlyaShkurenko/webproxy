<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Product;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ServerAdd extends AbstractHookListener
{
    const KEY = 'ServerAdd';
    protected $code = self::KEY;
}