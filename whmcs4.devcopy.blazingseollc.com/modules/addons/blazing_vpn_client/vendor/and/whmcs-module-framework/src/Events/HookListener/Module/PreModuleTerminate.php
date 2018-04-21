<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreModuleTerminate extends AbstractHookListener
{
    const KEY = 'PreModuleTerminate';
    protected $code = self::KEY;
}