<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Framework\Events\AbstractModuleListener as BaseModuleListener;

abstract class AbstractModuleListener extends BaseModuleListener
{
    protected $onExceptionVars = [];

    protected function onExecuteException(\Exception $e)
    {
        Logger::err(get_class($this) . ' exception: ' . $e->getMessage(), $this->onExceptionVars);

        return $e->getMessage();
    }
}
