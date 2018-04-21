<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class OrderProductPricingOverride extends AbstractHookListener
{
    const KEY = 'OrderProductPricingOverride';
    protected $code = self::KEY;
}