<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Cron;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class DailyCronJob extends AbstractHookListener
{
    const KEY = 'DailyCronJob';
    protected $code = self::KEY;
}