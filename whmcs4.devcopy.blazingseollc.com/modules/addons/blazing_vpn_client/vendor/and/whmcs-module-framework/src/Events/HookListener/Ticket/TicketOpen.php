<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Ticket;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class TicketOpen extends AbstractHookListener
{
    const KEY = 'TicketOpen';
    protected $code = self::KEY;
}