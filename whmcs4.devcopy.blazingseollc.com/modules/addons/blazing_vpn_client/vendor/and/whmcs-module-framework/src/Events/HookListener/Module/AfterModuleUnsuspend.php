<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterModuleUnsuspend extends AbstractHookListener
{
    const KEY = 'AfterModuleUnsuspend';
    protected $code = self::KEY;
}