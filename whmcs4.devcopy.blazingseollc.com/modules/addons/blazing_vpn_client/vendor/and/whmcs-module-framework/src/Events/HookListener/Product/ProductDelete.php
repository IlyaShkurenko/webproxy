<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Product;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ProductDelete extends AbstractHookListener
{
    const KEY = 'ProductDelete';
    protected $code = self::KEY;
}