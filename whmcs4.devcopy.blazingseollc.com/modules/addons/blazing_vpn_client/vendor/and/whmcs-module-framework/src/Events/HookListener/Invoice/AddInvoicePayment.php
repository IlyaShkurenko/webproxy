<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddInvoicePayment extends AbstractHookListener
{
    const KEY = 'AddInvoicePayment';
    protected $code = self::KEY;
}