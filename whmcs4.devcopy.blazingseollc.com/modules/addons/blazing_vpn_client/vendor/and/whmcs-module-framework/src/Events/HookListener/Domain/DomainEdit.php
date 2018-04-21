<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Domain;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class DomainEdit extends AbstractHookListener
{
    const KEY = 'DomainEdit';
    protected $code = self::KEY;
}