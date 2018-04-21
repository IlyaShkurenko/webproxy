<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\SupportTools;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AnnouncementAdd extends AbstractHookListener
{
    const KEY = 'AnnouncementAdd';
    protected $code = self::KEY;
}