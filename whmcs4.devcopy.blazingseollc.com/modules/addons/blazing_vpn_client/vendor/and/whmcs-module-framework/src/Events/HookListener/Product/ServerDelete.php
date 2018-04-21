<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Product;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ServerDelete extends AbstractHookListener
{
    const KEY = 'ServerDelete';
    protected $code = self::KEY;
}