<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoiceUnpaid extends AbstractHookListener
{
    const KEY = 'InvoiceUnpaid';
    protected $code = self::KEY;
}