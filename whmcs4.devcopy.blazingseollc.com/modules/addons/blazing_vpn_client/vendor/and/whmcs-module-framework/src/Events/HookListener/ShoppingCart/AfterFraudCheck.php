<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterFraudCheck extends AbstractHookListener
{
    const KEY = 'AfterFraudCheck';
    protected $code = self::KEY;
}