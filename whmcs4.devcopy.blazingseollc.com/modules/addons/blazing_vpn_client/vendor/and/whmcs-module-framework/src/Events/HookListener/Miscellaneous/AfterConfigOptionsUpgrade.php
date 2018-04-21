<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Miscellaneous;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterConfigOptionsUpgrade extends AbstractHookListener
{
    const KEY = 'AfterConfigOptionsUpgrade';
    protected $code = self::KEY;
}