<?php

namespace WHMCS\Module\Blazing\SettingsCustomizer;

use WHMCS\Module\Blazing\SettingsCustomizer\Vendor\WHMCS\Module\Framework\Helper;

class Patcher
{
    public function patchSettings()
    {
        Helper::conn()->update("UPDATE tblconfiguration SET value = 'on' WHERE setting = 'CCAttemptOnlyOnce'");
        Helper::restoreDb();
    }
}