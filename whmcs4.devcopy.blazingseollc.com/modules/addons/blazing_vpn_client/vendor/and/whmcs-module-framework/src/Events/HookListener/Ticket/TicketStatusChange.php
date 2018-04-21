<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Ticket;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class TicketStatusChange extends AbstractHookListener
{
    const KEY = 'TicketStatusChange';
    protected $code = self::KEY;
}