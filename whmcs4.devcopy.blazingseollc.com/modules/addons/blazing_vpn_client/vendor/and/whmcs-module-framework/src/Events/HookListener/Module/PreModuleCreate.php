<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Module;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreModuleCreate extends AbstractHookListener
{
    const KEY = 'PreModuleCreate';
    protected $code = self::KEY;
}