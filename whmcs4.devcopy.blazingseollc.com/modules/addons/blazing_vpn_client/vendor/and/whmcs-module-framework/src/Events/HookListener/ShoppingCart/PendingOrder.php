<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PendingOrder extends AbstractHookListener
{
    const KEY = 'PendingOrder';
    protected $code = self::KEY;
}