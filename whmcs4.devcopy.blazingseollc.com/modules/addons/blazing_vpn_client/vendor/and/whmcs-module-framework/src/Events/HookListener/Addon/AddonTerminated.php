<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonTerminated extends AbstractHookListener
{
    const KEY = 'AddonTerminated';
    protected $code = self::KEY;
}