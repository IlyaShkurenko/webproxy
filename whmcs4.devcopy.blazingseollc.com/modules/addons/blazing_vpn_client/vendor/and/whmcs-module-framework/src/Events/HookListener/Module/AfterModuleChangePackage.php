<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterModuleChangePackage extends AbstractHookListener
{
    const KEY = 'AfterModuleChangePackage';
    protected $code = self::KEY;
}