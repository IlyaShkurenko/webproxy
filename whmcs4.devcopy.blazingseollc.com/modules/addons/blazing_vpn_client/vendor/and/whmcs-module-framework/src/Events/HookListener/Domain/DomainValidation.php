<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Domain;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class DomainValidation extends AbstractHookListener
{
    const KEY = 'DomainValidation';
    protected $code = self::KEY;
}