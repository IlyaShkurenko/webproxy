<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonConfigSave extends AbstractHookListener
{
    const KEY = 'AddonConfigSave';
    protected $code = self::KEY;
}