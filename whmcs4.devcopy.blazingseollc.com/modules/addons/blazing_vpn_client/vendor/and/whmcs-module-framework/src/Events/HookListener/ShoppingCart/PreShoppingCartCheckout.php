<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreShoppingCartCheckout extends AbstractHookListener
{
    const KEY = 'PreShoppingCartCheckout';
    protected $code = self::KEY;
}