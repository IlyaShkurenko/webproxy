<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterInvoicingGenerateInvoiceItems extends AbstractHookListener
{
    const KEY = 'AfterInvoicingGenerateInvoiceItems';
    protected $code = self::KEY;
}