<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ViewOrderDetailsPage extends AbstractHookListener
{
    const KEY = 'ViewOrderDetailsPage';
    protected $code = self::KEY;
}