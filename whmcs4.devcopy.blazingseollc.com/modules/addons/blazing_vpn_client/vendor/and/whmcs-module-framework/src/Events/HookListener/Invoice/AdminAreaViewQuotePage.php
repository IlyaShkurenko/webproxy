<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminAreaViewQuotePage extends AbstractHookListener
{
    const KEY = 'AdminAreaViewQuotePage';
    protected $code = self::KEY;
}