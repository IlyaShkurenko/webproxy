<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreCalculateCartTotals extends AbstractHookListener
{
    const KEY = 'PreCalculateCartTotals';
    protected $code = self::KEY;
}