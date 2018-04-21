<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ShoppingCartValidateDomain extends AbstractHookListener
{
    const KEY = 'ShoppingCartValidateDomain';
    protected $code = self::KEY;
}