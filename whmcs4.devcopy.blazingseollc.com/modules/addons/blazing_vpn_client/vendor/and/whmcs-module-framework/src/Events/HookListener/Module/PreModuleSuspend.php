<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreModuleSuspend extends AbstractHookListener
{
    const KEY = 'PreModuleSuspend';
    protected $code = self::KEY;
}