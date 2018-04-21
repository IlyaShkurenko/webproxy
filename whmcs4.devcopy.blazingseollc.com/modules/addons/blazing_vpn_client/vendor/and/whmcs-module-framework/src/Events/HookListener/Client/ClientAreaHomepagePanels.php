<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientAreaHomepagePanels extends AbstractHookListener
{
    const KEY = 'ClientAreaHomepagePanels';
    protected $code = self::KEY;
}