<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoicePaid extends AbstractHookListener
{
    const KEY = 'InvoicePaid';
    protected $code = self::KEY;
}