<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class OverrideModuleUsernameGeneration extends AbstractHookListener
{
    const KEY = 'OverrideModuleUsernameGeneration';
    protected $code = self::KEY;
}