<?php

/** @var Application $app */

// Auth
use Silex\Application;

$app->get('/dashboard/', 'app.home_controller:index')->bind('index');
$app->get('/dashboard/login', 'app.main_controller:login')->bind('login');
$app->get('/dashboard/login-determine', 'app.main_controller:loginDetermine')->bind('loginDetermine');
$app->match('/dashboard/login-type', 'app.main_controller:loginType')->bind('loginType');
$app->get('/dashboard/logout', 'app.main_controller:logout')->bind('logout');
$app->get('/dashboard/re-login', 'app.main_controller:relogin')->bind('relogin');
$app->get('/code', 'app.main_controller:code')->bind('oauth_code');
$app->match('/dashboard/tfa', 'app.main_controller:tfa')->bind('tfa');

// Dashboard
$app->get('/dashboard/home', 'app.dashboard_controller:dashboard')->bind('dashboard');
$app->get('/dashboard/locations', 'app.dashboard_controller:locations')->bind('locations');
$app->match('/dashboard/sneaker', 'app.dashboard_controller:sneaker')->bind('sneaker');
$app->match('/dashboard/settings', 'app.dashboard_controller:settings')->bind('settings');
$app->match('/dashboard/settings/save', 'app.dashboard_controller:saveSettings')->bind('saveSettings');
$app->match('/dashboard/settings/rotation-time', 'app.dashboard_controller:setRotationTime')->bind('setRotationTime');
$app->match('/dashboard/replace', 'app.dashboard_controller:replace')->bind('replace');
$app->match('/dashboard/replace/multiple', 'app.dashboard_controller:replaceMultipleIp')->bind('replaceMultipleIp');
$app->match('/dashboard/replace/{id}', 'app.dashboard_controller:replaceIp')->bind('replaceIp');
$app->match('/dashboard/export/{type}', 'app.dashboard_controller:exportProxies')->bind('exportProxies');
$app->post('/save/locations', 'app.dashboard_controller:saveLocations')->bind('save_locations');
$app->get('/save/format/{format}', 'app.dashboard_controller:saveFormat')->bind('save_format');
$app->get('/add/ip/{ip}', 'app.dashboard_controller:addIp')->bind('add_ip');
$app->get('/remove/ip/{id}', 'app.dashboard_controller:removeIp')->bind('remove_ip');

// Checkout
$app->post('/checkout', 'app.checkout_controller:doCheckout')->bind('doCheckout');
$app->get('/checkout/do', 'app.checkout_controller:doCheckout')->bind('checkout_process');
$app->get('/checkout/{country}-{category}', 'app.checkout_controller:checkout')
    ->value('country', false)->value('category', false)
    ->bind('checkout');
$app->match('/checkout/total', 'app.checkout_controller:total')->bind('checkout_total');
$app->match('/checkout/promocode', 'app.checkout_controller:validatePromocode')->bind('checkout_promocode');
$app->match('/checkout/callback/whmcs', 'app.checkout_controller:callbackWhmcs')->bind('callback_whmcs');

// Checkout with Sign Up
$app->get('/purchase/{country}-{category}/', 'app.checkout_controller:quickBuy')->bind('quick_buy');
$app->get('/purchase/', 'app.checkout_controller:quickBuy')
    ->value('country', false)->value('category', false)
    ->bind('quick_buy_empty');
$app->get('/purchase/continue-tfa', 'app.checkout_controller:continueQuickBuy')->bind('quick_buy_continue_tfa');
$app->post('/purchase/process', 'app.checkout_controller:doQuickBuy')->bind('do_quick_buy');
$app->get('/quick-buy/check-email', 'app.main_controller:isEmailRegistered')->bind('quick_buy_check_email');

// Proxy requests
$app->get('/dashboard/api/export/4/all/{email}/{key}/list.csv', 'app.api_controller:proxyIPv4ListAction')->bind('bridge_proxy_list');
$app->get('/dashboard/api/export/6/all/{email}/{key}/list.csv', 'app.api_controller:proxyIPv6ListAction')->bind('api_export_ipv6');