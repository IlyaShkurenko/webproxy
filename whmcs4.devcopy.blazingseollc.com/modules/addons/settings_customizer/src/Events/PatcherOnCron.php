<?php

namespace WHMCS\Module\Blazing\SettingsCustomizer\Events;

use WHMCS\Module\Blazing\SettingsCustomizer\Patcher;
use WHMCS\Module\Blazing\SettingsCustomizer\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;

class PatcherOnCron extends AbstractHookListener
{
    protected $name = 'AfterCronJob';
    protected $priority = -50;

    /**
     * @return void
     */
    protected function execute()
    {
        $patcher = new Patcher();
        $patcher->patchSettings();
    }
}
