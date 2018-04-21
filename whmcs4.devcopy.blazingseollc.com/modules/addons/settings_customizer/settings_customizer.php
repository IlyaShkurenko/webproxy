<?php

use WHMCS\Module\Blazing\SettingsCustomizer\Vendor\WHMCS\Module\Framework\Addon;

require_once __DIR__ . '/bootstrap.php';

return Addon::registerModuleByFile(__FILE__, Addon::configBuilder()
    ->setName('Settings Customizer')
    ->setVersion('0.5')
    ->setDescription('WHMCS plugin to adjust some settings, turn them on/off, disallow to edit')
);