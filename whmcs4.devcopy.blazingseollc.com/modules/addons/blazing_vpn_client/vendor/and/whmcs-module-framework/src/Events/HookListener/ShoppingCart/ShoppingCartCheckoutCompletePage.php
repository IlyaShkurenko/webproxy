<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ShoppingCartCheckoutCompletePage extends AbstractHookListener
{
    const KEY = 'ShoppingCartCheckoutCompletePage';
    protected $code = self::KEY;
}