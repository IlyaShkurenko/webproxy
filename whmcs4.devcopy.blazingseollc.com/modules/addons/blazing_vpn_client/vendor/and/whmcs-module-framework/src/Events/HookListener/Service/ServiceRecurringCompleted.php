<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Service;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ServiceRecurringCompleted extends AbstractHookListener
{
    const KEY = 'ServiceRecurringCompleted';
    protected $code = self::KEY;
}