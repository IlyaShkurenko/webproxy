<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterShoppingCartCheckout extends AbstractHookListener
{
    const KEY = 'AfterShoppingCartCheckout';
    protected $code = self::KEY;
}