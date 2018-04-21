<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonSuspended extends AbstractHookListener
{
    const KEY = 'AddonSuspended';
    protected $code = self::KEY;
}