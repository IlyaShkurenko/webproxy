<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Miscellaneous;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class LogActivity extends AbstractHookListener
{
    const KEY = 'LogActivity';
    protected $code = self::KEY;
}