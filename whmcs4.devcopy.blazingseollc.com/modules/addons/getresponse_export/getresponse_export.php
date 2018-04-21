<?php

use WHMCS\Module\Blazing\Export\Vendor\WHMCS\Module\Framework\Addon;
use WHMCS\Module\Blazing\Export\Vendor\WHMCS\Module\Framework\ConfigBuilder\FieldConfigBuilder;

require_once __DIR__ . '/bootstrap.php';

Addon::registerModuleByFile(
    __FILE__,
    Addon::configBuilder()
        ->setName('GetResponse export')
        ->setDescription('Exporting user data to GetResponse contacts.')
        ->setVersion('1.0')
        ->setAuthor('Blazing')
        ->addFields(
            [
                FieldConfigBuilder::text('apiKey', 'API key')
                    ->setDescription('GetResponse application key'),
                FieldConfigBuilder::text('campaignId', 'Campaign id')
                    ->setDescription('GetResponse campaign id'),
            ]
        )
)
    ->registerModuleListeners(
        [
            \WHMCS\Module\Blazing\Export\GetResponse\Listener\Activated::class,
            \WHMCS\Module\Blazing\Export\GetResponse\Listener\Output::class,
        ]
    );