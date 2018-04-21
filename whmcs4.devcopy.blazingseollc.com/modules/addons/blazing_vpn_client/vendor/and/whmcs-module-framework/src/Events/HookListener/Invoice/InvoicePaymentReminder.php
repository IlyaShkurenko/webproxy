<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoicePaymentReminder extends AbstractHookListener
{
    const KEY = 'InvoicePaymentReminder';
    protected $code = self::KEY;
}