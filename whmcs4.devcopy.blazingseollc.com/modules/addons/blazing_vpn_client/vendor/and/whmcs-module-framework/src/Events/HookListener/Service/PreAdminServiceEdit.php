<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Service;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreAdminServiceEdit extends AbstractHookListener
{
    const KEY = 'PreAdminServiceEdit';
    protected $code = self::KEY;
}