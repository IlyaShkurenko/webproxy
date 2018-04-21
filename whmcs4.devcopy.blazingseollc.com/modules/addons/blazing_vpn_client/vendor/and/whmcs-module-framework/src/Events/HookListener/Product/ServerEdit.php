<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Product;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ServerEdit extends AbstractHookListener
{
    const KEY = 'ServerEdit';
    protected $code = self::KEY;
}