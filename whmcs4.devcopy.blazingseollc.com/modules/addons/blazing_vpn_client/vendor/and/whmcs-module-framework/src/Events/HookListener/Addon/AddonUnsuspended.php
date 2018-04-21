<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonUnsuspended extends AbstractHookListener
{
    const KEY = 'AddonUnsuspended';
    protected $code = self::KEY;
}