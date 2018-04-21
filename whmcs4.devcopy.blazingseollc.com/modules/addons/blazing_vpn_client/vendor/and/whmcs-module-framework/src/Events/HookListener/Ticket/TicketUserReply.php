<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Ticket;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class TicketUserReply extends AbstractHookListener
{
    const KEY = 'TicketUserReply';
    protected $code = self::KEY;
}