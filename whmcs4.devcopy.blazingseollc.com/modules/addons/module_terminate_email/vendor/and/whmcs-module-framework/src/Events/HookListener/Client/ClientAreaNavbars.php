<?php

namespace WHMCS\Module\Framework\Events\HookListener\Client;

use WHMCS\Module\Framework\Events\AbstractHookListener;

abstract class ClientAreaNavbars extends AbstractHookListener
{
    const KEY = 'ClientAreaNavbars';

    protected $code = self::KEY;
}