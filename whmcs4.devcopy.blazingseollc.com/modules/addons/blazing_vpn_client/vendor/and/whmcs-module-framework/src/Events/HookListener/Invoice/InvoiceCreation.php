<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoiceCreation extends AbstractHookListener
{
    const KEY = 'InvoiceCreation';
    protected $code = self::KEY;
}