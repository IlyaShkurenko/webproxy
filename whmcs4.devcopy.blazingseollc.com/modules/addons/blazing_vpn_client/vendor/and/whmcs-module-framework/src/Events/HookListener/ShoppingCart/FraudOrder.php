<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class FraudOrder extends AbstractHookListener
{
    const KEY = 'FraudOrder';
    protected $code = self::KEY;
}