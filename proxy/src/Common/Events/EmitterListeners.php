<?php

namespace Common\Events;

use ProxyReseller\Listeners\PreventProxyAssignmentOnNegativeBalanceListener;

interface EmitterListeners
{
    const LISTENERS = [
        PreventProxyAssignmentOnNegativeBalanceListener::class
    ];
}
