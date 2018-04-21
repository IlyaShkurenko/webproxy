<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Ticket;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminAreaViewTicketPage extends AbstractHookListener
{
    const KEY = 'AdminAreaViewTicketPage';
    protected $code = self::KEY;
}