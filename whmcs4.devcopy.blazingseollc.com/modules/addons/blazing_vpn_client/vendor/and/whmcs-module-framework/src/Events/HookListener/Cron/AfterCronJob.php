<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Cron;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterCronJob extends AbstractHookListener
{
    const KEY = 'AfterCronJob';
    protected $code = self::KEY;
}