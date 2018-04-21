<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Product;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminProductConfigFieldsSave extends AbstractHookListener
{
    const KEY = 'AdminProductConfigFieldsSave';
    protected $code = self::KEY;
}