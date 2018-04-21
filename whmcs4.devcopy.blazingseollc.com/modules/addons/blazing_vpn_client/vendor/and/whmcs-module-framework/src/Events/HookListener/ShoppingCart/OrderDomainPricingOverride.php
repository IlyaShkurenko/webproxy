<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class OrderDomainPricingOverride extends AbstractHookListener
{
    const KEY = 'OrderDomainPricingOverride';
    protected $code = self::KEY;
}