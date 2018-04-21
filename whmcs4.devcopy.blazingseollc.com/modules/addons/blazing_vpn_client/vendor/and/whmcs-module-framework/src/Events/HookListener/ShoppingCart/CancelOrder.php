<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class CancelOrder extends AbstractHookListener
{
    const KEY = 'CancelOrder';
    protected $code = self::KEY;
}