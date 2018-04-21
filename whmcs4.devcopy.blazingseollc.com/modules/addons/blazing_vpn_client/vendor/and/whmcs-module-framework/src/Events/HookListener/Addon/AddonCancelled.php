<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonCancelled extends AbstractHookListener
{
    const KEY = 'AddonCancelled';
    protected $code = self::KEY;
}