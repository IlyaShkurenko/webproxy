<?php

use WHMCS\Module\Blazing\SettingsCustomizer\Events\PatcherOnCron;
use WHMCS\Module\Blazing\SettingsCustomizer\Hooks\DisallowAutomationSettings;
use WHMCS\Module\Blazing\SettingsCustomizer\Vendor\WHMCS\Module\Framework\ModuleHooks;

require_once __DIR__ . '/bootstrap.php';

ModuleHooks::registerHooks(__FILE__, [
    DisallowAutomationSettings::class,
    PatcherOnCron::class
]);