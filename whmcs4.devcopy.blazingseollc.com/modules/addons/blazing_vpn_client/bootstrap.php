<?php

use Blazing\Vpn\Client\Container;
use Blazing\Vpn\Client\Vendor\ApiRequestHandler\ApiConfiguration;
use Blazing\Vpn\Client\Vendor\Blazing\Logger\Logger;
use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Addon;
use Blazing\Vpn\Client\VpnApi;

$bootstrap = require __DIR__ . '/vendor/and/whmcs-module-framework/bootstrapper.php';
$bootstrap();

$container = Container::registerInstance();
$container->setLoggerBuilder(function() {
    $logger = Logger::createRotatingFileLogger(__DIR__ . '/logs/all.log', 90);

    return $logger;
});
$container->setVpnApiBuilder(function() use ($container) {
    $module = Addon::getInstanceById('blazing_vpn_client');

    return new VpnApi(
        ApiConfiguration::build(
            trim($module->getConfig('apiDomain'), '/'),
            '/' . ltrim($module->getConfig('apiUrl'), '/')
        )->setLogger($container->getLogger())
    );
});