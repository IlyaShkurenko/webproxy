<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Cron;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class DailyCronJobPreEmail extends AbstractHookListener
{
    const KEY = 'DailyCronJobPreEmail';
    protected $code = self::KEY;
}