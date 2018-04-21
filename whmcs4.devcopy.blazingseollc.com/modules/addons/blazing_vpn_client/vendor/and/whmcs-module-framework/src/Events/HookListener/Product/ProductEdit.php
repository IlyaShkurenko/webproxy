<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Product;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ProductEdit extends AbstractHookListener
{
    const KEY = 'ProductEdit';
    protected $code = self::KEY;
}