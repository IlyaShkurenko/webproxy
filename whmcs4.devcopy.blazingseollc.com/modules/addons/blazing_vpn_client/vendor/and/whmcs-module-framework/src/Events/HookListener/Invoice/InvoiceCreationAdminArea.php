<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class InvoiceCreationAdminArea extends AbstractHookListener
{
    const KEY = 'InvoiceCreationAdminArea';
    protected $code = self::KEY;
}