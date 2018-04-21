<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonAdd extends AbstractHookListener
{
    const KEY = 'AddonAdd';
    protected $code = self::KEY;
}