<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterModuleChangePassword extends AbstractHookListener
{
    const KEY = 'AfterModuleChangePassword';
    protected $code = self::KEY;
}