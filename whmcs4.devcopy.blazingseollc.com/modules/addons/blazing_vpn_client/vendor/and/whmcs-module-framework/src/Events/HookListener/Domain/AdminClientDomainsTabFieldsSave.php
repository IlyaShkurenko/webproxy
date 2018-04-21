<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Domain;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminClientDomainsTabFieldsSave extends AbstractHookListener
{
    const KEY = 'AdminClientDomainsTabFieldsSave';
    protected $code = self::KEY;
}