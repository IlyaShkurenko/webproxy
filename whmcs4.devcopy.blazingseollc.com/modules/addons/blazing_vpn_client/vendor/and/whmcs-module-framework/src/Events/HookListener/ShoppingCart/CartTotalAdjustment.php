<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class CartTotalAdjustment extends AbstractHookListener
{
    const KEY = 'CartTotalAdjustment';
    protected $code = self::KEY;
}