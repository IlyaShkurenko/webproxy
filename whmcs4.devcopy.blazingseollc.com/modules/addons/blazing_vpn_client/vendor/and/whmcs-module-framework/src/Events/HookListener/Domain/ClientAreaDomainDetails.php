<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Domain;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientAreaDomainDetails extends AbstractHookListener
{
    const KEY = 'ClientAreaDomainDetails';
    protected $code = self::KEY;
}