<?php

namespace WHMCS\Module\Blazing\SettingsCustomizer\Hooks;

use WHMCS\Module\Blazing\SettingsCustomizer\Vendor\WHMCS\Module\Framework\PageHooks\AbstractPageHookFacadeListener;
use WHMCS\Module\Blazing\SettingsCustomizer\Vendor\WHMCS\Module\Framework\PageHooks\Admin\CustomAdminPageHook;

class DisallowAutomationSettings extends AbstractPageHookFacadeListener
{

    protected $pageHookClass = CustomAdminPageHook::class;
    protected $template = 'configauto';
    protected $position = CustomAdminPageHook::POSITION_BODY_BOTTOM;

    public function execute()
    {
        return $this->view('automation-settings.tpl');
    }
}
