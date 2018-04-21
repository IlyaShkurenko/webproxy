<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoiceCancelled extends AbstractHookListener
{
    const KEY = 'InvoiceCancelled';
    protected $code = self::KEY;
}