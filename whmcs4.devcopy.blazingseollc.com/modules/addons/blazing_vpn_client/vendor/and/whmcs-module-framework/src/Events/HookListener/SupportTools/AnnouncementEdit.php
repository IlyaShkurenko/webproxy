<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\SupportTools;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AnnouncementEdit extends AbstractHookListener
{
    const KEY = 'AnnouncementEdit';
    protected $code = self::KEY;
}