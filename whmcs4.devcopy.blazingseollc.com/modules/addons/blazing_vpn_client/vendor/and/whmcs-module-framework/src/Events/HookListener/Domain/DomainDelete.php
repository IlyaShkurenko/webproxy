<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Domain;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class DomainDelete extends AbstractHookListener
{
    const KEY = 'DomainDelete';
    protected $code = self::KEY;
}