<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonEdit extends AbstractHookListener
{
    const KEY = 'AddonEdit';
    protected $code = self::KEY;
}