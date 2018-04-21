<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Cron;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreCronJob extends AbstractHookListener
{
    const KEY = 'PreCronJob';
    protected $code = self::KEY;
}