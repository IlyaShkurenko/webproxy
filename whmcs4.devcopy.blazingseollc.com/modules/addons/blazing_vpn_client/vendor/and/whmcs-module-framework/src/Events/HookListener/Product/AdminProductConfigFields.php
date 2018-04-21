<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Product;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminProductConfigFields extends AbstractHookListener
{
    const KEY = 'AdminProductConfigFields';
    protected $code = self::KEY;
}