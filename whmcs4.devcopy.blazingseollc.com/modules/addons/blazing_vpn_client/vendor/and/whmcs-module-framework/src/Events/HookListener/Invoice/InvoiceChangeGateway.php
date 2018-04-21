<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoiceChangeGateway extends AbstractHookListener
{
    const KEY = 'InvoiceChangeGateway';
    protected $code = self::KEY;
}