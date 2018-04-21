<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Product;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterProductUpgrade extends AbstractHookListener
{
    const KEY = 'AfterProductUpgrade';
    protected $code = self::KEY;
}