<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterModuleSuspend extends AbstractHookListener
{
    const KEY = 'AfterModuleSuspend';
    protected $code = self::KEY;
}