<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Service;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ServiceEdit extends AbstractHookListener
{
    const KEY = 'ServiceEdit';
    protected $code = self::KEY;
}