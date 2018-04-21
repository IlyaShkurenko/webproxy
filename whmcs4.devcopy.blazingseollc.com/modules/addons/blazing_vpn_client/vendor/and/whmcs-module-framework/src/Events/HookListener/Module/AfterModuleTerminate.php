<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterModuleTerminate extends AbstractHookListener
{
    const KEY = 'AfterModuleTerminate';
    protected $code = self::KEY;
}