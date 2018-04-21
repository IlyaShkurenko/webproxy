<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ShoppingCartValidateDomainsConfig extends AbstractHookListener
{
    const KEY = 'ShoppingCartValidateDomainsConfig';
    protected $code = self::KEY;
}