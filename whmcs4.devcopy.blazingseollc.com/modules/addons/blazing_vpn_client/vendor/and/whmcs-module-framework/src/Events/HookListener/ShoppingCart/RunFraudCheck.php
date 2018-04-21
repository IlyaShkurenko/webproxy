<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class RunFraudCheck extends AbstractHookListener
{
    const KEY = 'RunFraudCheck';
    protected $code = self::KEY;
}