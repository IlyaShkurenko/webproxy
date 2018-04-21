<?php

namespace Blazing\Vpn\Client\Events;

use Blazing\Vpn\Client\Container;
use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Addon;
use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractModuleListener;

abstract class AbstractFunction extends AbstractModuleListener
{
    protected function onExecuteException(\Exception $e)
    {
        Container::getInstance()->getLogger()->err(get_class($this) . ' exception: ' . $e->getMessage());

        return 'Exception: ' . $e->getMessage();
    }

    protected function getTemplatesDir()
    {
        return Addon::getInstanceById($this->getModule()->getId())->getDirectory() . "/templates";
    }
}
