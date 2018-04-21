<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoicePaidPreEmail extends AbstractHookListener
{
    const KEY = 'InvoicePaidPreEmail';
    protected $code = self::KEY;
}