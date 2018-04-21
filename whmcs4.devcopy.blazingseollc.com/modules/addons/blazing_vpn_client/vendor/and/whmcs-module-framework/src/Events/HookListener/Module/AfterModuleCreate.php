<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterModuleCreate extends AbstractHookListener
{
    const KEY = 'AfterModuleCreate';
    protected $code = self::KEY;
}