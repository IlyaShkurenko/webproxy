<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoiceRefunded extends AbstractHookListener
{
    const KEY = 'InvoiceRefunded';
    protected $code = self::KEY;
}