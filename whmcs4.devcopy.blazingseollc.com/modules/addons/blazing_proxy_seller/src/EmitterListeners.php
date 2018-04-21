<?php

namespace WHMCS\Module\Blazing\Proxy\Seller;

use WHMCS\Module\Blazing\Proxy\Seller\Events;

interface EmitterListeners
{
    const LISTENERS = [
        Events\Integration\FindTrueProductId::class,
        Events\Integration\FindTrueProductPrice::class,
    ];
}
