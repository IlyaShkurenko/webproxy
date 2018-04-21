<?php

use Silex\Application;

/** @var Application $app */
/** @var Application $api */

foreach ([
    'app.proxy.plist_ipv6' => Proxy\Controller\ProxyListIPv6Controller::class,

] as $key => $controller) {
    $app[$key] = $app->share(
        function () use ($app, $controller) {
            return new $controller($app);
        }
    );
}

$api->mount('/sys/ipv6/', $endpoint = $app['controllers_factory']);

$endpoint->get('/blocks-allocation.csv', 'app.proxy.plist_ipv6:getBlocksAllocationAction');
$endpoint->get('/packages.csv', 'app.proxy.plist_ipv6:getUserPackagesAction');
