<?php

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Addon;
use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\ConfigBuilder\FieldConfigBuilder;

require_once __DIR__ . '/bootstrap.php';

return Addon::registerModuleByFile(__FILE__,
    Addon::configBuilder()
    ->setName('Blazing VPN Client')
    ->setAuthor('Sprious <and.webdev[at]gmail.com>')
    ->setVersion('0.5 ' . '<sup style="color: #f79a22">alpha</sup>')
    ->addFields([
        FieldConfigBuilder::text('apiDomain', 'API domain')
            ->setDescription('Default value is: proxy.blazingseollc.com')
            ->setDefaultValue('proxy.blazingseollc.com'),
        FieldConfigBuilder::text('apiUrl', 'API url')
            ->setDescription('Default value is: /vpn/api/v1.0')
            ->setDefaultValue('/vpn/api/v1.0')
    ])
);