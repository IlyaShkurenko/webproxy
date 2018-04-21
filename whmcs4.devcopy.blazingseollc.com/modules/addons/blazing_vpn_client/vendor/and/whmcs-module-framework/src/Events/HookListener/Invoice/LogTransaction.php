<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class LogTransaction extends AbstractHookListener
{
    const KEY = 'LogTransaction';
    protected $code = self::KEY;
}