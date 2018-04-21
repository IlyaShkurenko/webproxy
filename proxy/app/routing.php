<?php

/** @var \Silex\Application $app */

$symlink = [
    'reseller' => defined('URL_SYMLINK_RESELLER') and URL_SYMLINK_RESELLER,
    'api' => defined('URL_SYMLINK_API') and URL_SYMLINK_API,
    'proxy' => defined('URL_SYMLINK_PROXY') and URL_SYMLINK_PROXY,
];

// # Dashboard

$app->mount(!$symlink['reseller'] ? '/reseller' : '/', $reseller = $app['controllers_factory']);

$reseller->match('/login', 'app.dashboard_controller:loginAction')->bind('login');
$reseller->match('/', 'app.dashboard_controller:indexAction')->bind('dashboard');
$reseller->match('/credits/{amount}', 'app.dashboard_controller:addCredits')->bind('credits');

$reseller->match('/dashboard/login', 'controller.proxyReseller.dashboard:loginAction')->bind('reseller.login');
$reseller->match('/dashboard/credits/{amount}', 'controller.proxyReseller.dashboard:addCredits')->bind('reseller.credits');
$reseller->match('/dashboard', 'controller.proxyReseller.dashboard:indexAction')->bind('reseller.dashboard');

// Dummy AMember handler
$reseller->post('/dummy-am/login', 'app.dashboard_am_dummy:loginAction')->bind('dam_login');
$reseller->get('/dummy-am/logout', 'app.dashboard_am_dummy:logoutAction')->bind('dam_logout');
$reseller->get('/dummy-am/charge', 'app.dashboard_am_dummy:chargeAction')->bind('dam_charge');

// # Reseller API

$reseller->mount('/api', $resellerApi = $app['controllers_factory']);

// Get user
$resellerApi->get('/proxy/user', 'app.api_proxy_v1_user:listAction');
$resellerApi->get('/proxy/user/{id}', 'app.api_proxy_v1_user:getAction')->bind('user_get');

// Add/remove user
$resellerApi->post('/proxy/user', 'app.api_proxy_v1_user:addAction');
$resellerApi->delete('/proxy/user/{id}', 'app.api_proxy_v1_user:removeAction');

// Settings
$resellerApi->patch('/proxy/user/{id}/settings', 'app.api_proxy_v1_user:updateAction');
$resellerApi->post('/proxy/user/{id}/settings', 'app.api_proxy_v1_user:updateAction');

// Replace proxy
$resellerApi->patch('/proxy/user/{id}/replace', 'app.api_proxy_v1_user:replaceAction');
$resellerApi->post('/proxy/user/{id}/replace', 'app.api_proxy_v1_user:replaceAction');

// Set location
$resellerApi->patch('/proxy/user/{id}/plan/{country}/{category}/locations', 'app.api_proxy_v1_user:locationAction');
$resellerApi->post('/proxy/user/{id}/plan/{country}/{category}/locations', 'app.api_proxy_v1_user:locationAction');

// Plan
$resellerApi->post('/proxy/user/{id}/plan', 'app.api_proxy_v1_user:updatePlanAction');
$resellerApi->post('/proxy/user/{id}/plan/expiration', 'app.api_proxy_v1_user:updatePlanExpirationAction');
$resellerApi->delete('/proxy/user/{id}/plan/{country}/{category}', 'app.api_proxy_v1_user:deletePlanAction');
$resellerApi->post('/proxy/user/{id}/quickAssign', 'app.api_proxy_v1_user:quickAssignAction');

// IP
$resellerApi->post('/proxy/user/{id}/ip', 'app.api_proxy_v1_user:addIp');
$resellerApi->delete('/proxy/user/{id}/ip', 'app.api_proxy_v1_user:deleteIp');

// Balance, pricing tiers
$resellerApi->get('/proxy/balance', 'app.api_proxy_v1_reseller:balanceAction');
$resellerApi->get('/proxy/pricing', 'app.api_proxy_v1_reseller:pricingAction');

// Info
$resellerApi->get('/proxy/countries', 'app.api_proxy_v1_info:countryAction');
$resellerApi->get('/proxy/locations', 'app.api_proxy_v1_info:locationAction');

// # Proxy

$app->mount(!$symlink['api'] ? '/api' : '/', $api = $app['controllers_factory']);
$app->mount(!$symlink['proxy'] ? '/proxy' : '/', $proxy = $app['controllers_factory']);

$api->get('/sys/proxy-list/{type}.{ext}', 'app.proxy.plist:listAction')->assert('type', 'ip|pwd')->assert('ext', 'csv');
$api->get('/sys/proxy-list/{type}.{ext}', 'app.proxy.plist:lastRotatedTimestampAction')->assert('type', 'timestamp')->assert('ext', 'csv');
$api->get('/sys/proxy-list/rotating/ip.{ext}', 'app.proxy.plist:rotatingProxiesIpAction')->assert('ext', 'json');
$api->get('/sys/proxy-list/rotating/ports.{ext}', 'app.proxy.plist:rotatingProxiesPortsAction')->assert('ext', 'json');
$api->get('/sys/proxy-list/rotating/threads.{ext}', 'app.proxy.plist:rotatingProxiesThreadsAction')->assert('ext', 'json');
$api->get('/sys/source-ranges.csv', 'app.proxy.plist:sourcesRangesAction');
$api->get('/sys/proxy-list/acl.{ext}', 'app.proxy.plist:getAclAction')->assert('ext', 'csv');
$api->get('/sys/proxy-list/acl-terms.{ext}', 'app.proxy.plist:getAclTermsAction')->assert('ext', 'csv');
$api->get('/sys/proxy-list/dead.{ext}', 'app.proxy.plist:getDeadProxiesAction')->assert('ext', 'csv');
$api->get('/sys/proxy-list/emulate-auth.{ext}', 'app.proxy.plist:emulateAuth')->assert('ext', 'json');

// Info
$api->post('/sys/info/get-user-by-whmcs-id', 'app.proxy.sysInfo:getUserByWhmcsId');
$api->match('/sys/info/get-user-by-auth-credentials.csv', 'app.proxy.sysInfo:getUserByAuthCredentials');
$api->match('/sys/info/ping', 'app.proxy.sysInfo:ping');

// User
$proxy->get('/list.php', 'app.proxy.plist:proxiesListAction');

// # Proxy API
$api->post('/proxy/ports-sync', 'app.proxy.api:forcePortsSync');

// Outdated
$app->get('/proxy-list/{type}.{ext}', 'app.proxy.plist:listAction')->assert('type', 'ip|pwd')->assert('ext', 'csv');
$app->get('/proxy-list/{type}.{ext}', 'app.proxy.plist:lastRotatedTimestampAction')->assert('type', 'timestamp')->assert('ext', 'csv');
$app->get('/add.php', 'app.proxy.plist:sourcesRangesAction');

// Deprecated
$app->get('/plist.ip.{ext}', 'app.proxy.plist:ipAction')->assert('ext', '(php|csv)');
$app->get('/plist.pw.{ext}', 'app.proxy.plist:pwdAction')->assert('ext', '(php|csv)');
$app->get('/plist.change.{ext}', 'app.proxy.plist:lastRotatedTimestampAction')->assert('ext', '(php|csv)');

// # IPv6 Sys API
require __DIR__ . '/routing/proxy.api.sys.ipv6.php';

// # Reseller API v2
require __DIR__ . '/routing/proxy_reseller.api.v20.php';
require __DIR__ . '/routing/proxy_reseller.api.v21.php';

// # Dashboard
require __DIR__ . '/routing/proxy.dashboard.php';

// Content root
$app->get('/', 'app.proxy.not_found:indexAction')->bind('web_dir');