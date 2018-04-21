<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoiceSplit extends AbstractHookListener
{
    const KEY = 'InvoiceSplit';
    protected $code = self::KEY;
}