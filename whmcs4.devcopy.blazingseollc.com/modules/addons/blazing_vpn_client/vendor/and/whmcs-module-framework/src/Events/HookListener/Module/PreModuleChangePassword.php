<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreModuleChangePassword extends AbstractHookListener
{
    const KEY = 'PreModuleChangePassword';
    protected $code = self::KEY;
}