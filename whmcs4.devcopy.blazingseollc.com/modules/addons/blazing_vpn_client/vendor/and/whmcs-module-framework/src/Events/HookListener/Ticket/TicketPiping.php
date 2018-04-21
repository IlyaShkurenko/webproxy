<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Ticket;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class TicketPiping extends AbstractHookListener
{
    const KEY = 'TicketPiping';
    protected $code = self::KEY;
}