<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Service;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreServiceEdit extends AbstractHookListener
{
    const KEY = 'PreServiceEdit';
    protected $code = self::KEY;
}