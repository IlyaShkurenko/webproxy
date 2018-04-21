<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreInvoicingGenerateInvoiceItems extends AbstractHookListener
{
    const KEY = 'PreInvoicingGenerateInvoiceItems';
    protected $code = self::KEY;
}