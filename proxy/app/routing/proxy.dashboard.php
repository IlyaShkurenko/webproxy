<?php

use Buzz\Message\Request;
use Proxy\Controller\Dashboard\DashboardController;
use Proxy\Controller\Dashboard\IPv6Controller;
use Proxy\Controller\Dashboard\LoginController;
use Silex\Application;

/** @var Application $app */
/** @var Application $proxy */

foreach ([
    'controller.proxy.dashboard.login' => LoginController::class,
    'controller.proxy.dashboard.dashboard' => DashboardController::class,
    'controller.proxy.dashboard.ipv6' => IPv6Controller::class,
] as $key => $controller) {
    $app[$key] = $app->share(
        function () use ($app, $controller) {
            return new $controller($app);
        }
    );
}

$proxy->mount('/management', $dashboard = $app['controllers_factory']);

// Generic
$dashboard->get('/', 'controller.proxy.dashboard.dashboard:indexAction')->bind('proxy_dashboard_index');
$dashboard->get('/login.html', 'controller.proxy.dashboard.login:login')->bind('proxy_dashboard_login');
$dashboard->get('/logout.html', 'controller.proxy.dashboard.login:logout')->bind('proxy_dashboard_logout');
$dashboard->get('/code', 'controller.proxy.dashboard.login:code')->bind('proxy_dashboard_whmcs_callback');

// Main
$dashboard->match('/ipv4/import-ips.html', 'controller.proxy.dashboard.dashboard:importIpsAction')->bind('proxy_dashboard_ipv4_import');

// IPv6
$dashboard->match('/ipv6/import-blocks.html', 'controller.proxy.dashboard.ipv6:importBlocksAction')->bind('proxy_dashboard_ipv6_import');