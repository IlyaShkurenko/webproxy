<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoiceCreated extends AbstractHookListener
{
    const KEY = 'InvoiceCreated';
    protected $code = self::KEY;
}