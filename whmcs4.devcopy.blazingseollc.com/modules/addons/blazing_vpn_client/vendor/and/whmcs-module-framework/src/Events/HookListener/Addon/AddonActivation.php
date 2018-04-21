<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonActivation extends AbstractHookListener
{
    const KEY = 'AddonActivation';
    protected $code = self::KEY;
}