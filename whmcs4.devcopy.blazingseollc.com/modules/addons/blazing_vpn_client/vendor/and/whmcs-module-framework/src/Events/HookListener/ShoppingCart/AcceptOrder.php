<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AcceptOrder extends AbstractHookListener
{
    const KEY = 'AcceptOrder';
    protected $code = self::KEY;
}