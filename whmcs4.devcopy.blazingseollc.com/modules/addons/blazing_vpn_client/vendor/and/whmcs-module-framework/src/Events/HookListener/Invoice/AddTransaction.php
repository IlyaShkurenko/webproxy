<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddTransaction extends AbstractHookListener
{
    const KEY = 'AddTransaction';
    protected $code = self::KEY;
}