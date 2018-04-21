<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonConfig extends AbstractHookListener
{
    const KEY = 'AddonConfig';
    protected $code = self::KEY;
}