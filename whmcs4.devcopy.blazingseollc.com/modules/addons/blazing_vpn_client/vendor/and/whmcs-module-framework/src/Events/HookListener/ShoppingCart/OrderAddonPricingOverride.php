<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class OrderAddonPricingOverride extends AbstractHookListener
{
    const KEY = 'OrderAddonPricingOverride';
    protected $code = self::KEY;
}