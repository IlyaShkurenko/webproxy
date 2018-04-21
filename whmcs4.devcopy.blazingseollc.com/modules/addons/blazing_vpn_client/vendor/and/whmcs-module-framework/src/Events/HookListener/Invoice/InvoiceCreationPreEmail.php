<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoiceCreationPreEmail extends AbstractHookListener
{
    const KEY = 'InvoiceCreationPreEmail';
    protected $code = self::KEY;
}