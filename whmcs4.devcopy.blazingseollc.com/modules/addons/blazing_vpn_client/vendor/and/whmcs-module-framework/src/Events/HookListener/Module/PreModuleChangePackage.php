<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreModuleChangePackage extends AbstractHookListener
{
    const KEY = 'PreModuleChangePackage';
    protected $code = self::KEY;
}