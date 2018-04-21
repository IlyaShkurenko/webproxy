<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonFraud extends AbstractHookListener
{
    const KEY = 'AddonFraud';
    protected $code = self::KEY;
}