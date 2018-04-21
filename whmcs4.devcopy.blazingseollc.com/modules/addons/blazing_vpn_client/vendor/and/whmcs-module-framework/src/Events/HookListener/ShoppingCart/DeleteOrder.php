<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class DeleteOrder extends AbstractHookListener
{
    const KEY = 'DeleteOrder';
    protected $code = self::KEY;
}