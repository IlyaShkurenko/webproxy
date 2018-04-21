<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Domain;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreDomainRegister extends AbstractHookListener
{
    const KEY = 'PreDomainRegister';
    protected $code = self::KEY;
}