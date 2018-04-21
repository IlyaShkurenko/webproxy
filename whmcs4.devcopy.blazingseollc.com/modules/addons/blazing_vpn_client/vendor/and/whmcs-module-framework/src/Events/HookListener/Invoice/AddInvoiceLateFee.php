<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddInvoiceLateFee extends AbstractHookListener
{
    const KEY = 'AddInvoiceLateFee';
    protected $code = self::KEY;
}