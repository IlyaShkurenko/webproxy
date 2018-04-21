<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\ShoppingCart;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class OverrideOrderNumberGeneration extends AbstractHookListener
{
    const KEY = 'OverrideOrderNumberGeneration';
    protected $code = self::KEY;
}