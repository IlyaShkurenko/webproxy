<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Domain;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminClientDomainsTabFields extends AbstractHookListener
{
    const KEY = 'AdminClientDomainsTabFields';
    protected $code = self::KEY;
}