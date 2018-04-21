<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonActivated extends AbstractHookListener
{
    const KEY = 'AddonActivated';
    protected $code = self::KEY;
}