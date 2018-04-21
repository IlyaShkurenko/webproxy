<?php

use Silex\Application;

/** @var Application $app */
/** @var Application $api */

foreach ([
    'app.api.reseller.v21.misc'           => ProxyReseller\Controller\ApiV21\MiscController::class,
    'app.api.reseller.v21.user'           => ProxyReseller\Controller\ApiV21\UserController::class,
    'app.api.reseller.v21.userManagement' => ProxyReseller\Controller\ApiV21\UserManagementController::class,
    'app.api.reseller.v21.packages'       => ProxyReseller\Controller\ApiV21\PackagesController::class,
    'app.api.reseller.v21.portsIPv4'      => ProxyReseller\Controller\ApiV21\PortsIPv4Controller::class,
    'app.api.reseller.v21.portsIPv6'      => ProxyReseller\Controller\ApiV21\PortsIPv6Controller::class,
    'app.api.reseller.v21.mta'            => ProxyReseller\Controller\ApiV21\MtaController::class,

] as $key => $controller) {
    $app[$key] = $app->share(
        function () use ($app, $controller) {
            return new $controller($app);
        }
    );
}

$api->mount('/reseller/v2.1', $resellerApi = $app['controllers_factory']);

// User management
$resellerApi->get('/users/find/byBillingId/{source}/{id}', 'app.api.reseller.v21.userManagement:getUserIdByBillingAction');
$resellerApi->get('/users/find/byLoginOrEmail/{loginOrEmail}', 'app.api.reseller.v21.userManagement:findUserByLoginOrEmail');
$resellerApi->post('/users/upsert/{source}/{id}', 'app.api.reseller.v21.userManagement:upsertUserAction');

// User
$resellerApi->get('/user/{userId}/details', 'app.api.reseller.v21.user:getDetailsAction')->bind('apiv21.user.details');
$resellerApi->post('/user/{userId}/details/sneakerLocation', 'app.api.reseller.v21.user:updateSneakerLocationAction');
$resellerApi->post('/user/{userId}/details', 'app.api.reseller.v21.user:updateSettingsAction');
$resellerApi->get('/user/{userId}/details/auth/ip', 'app.api.reseller.v21.user:getAuthIpListAction');
$resellerApi->post('/user/{userId}/details/auth/ip/{ip}', 'app.api.reseller.v21.user:addAuthIpAction');
$resellerApi->delete('/user/{userId}/details/auth/ip/{ipId}', 'app.api.reseller.v21.user:deleteAuthIpAction');

// Packages
$resellerApi->get('/user/{userId}/packages', 'app.api.reseller.v21.packages:getAllAction');
$resellerApi->delete('/user/{userId}/packages', 'app.api.reseller.v21.packages:deleteAllAction');
$resellerApi->get('/user/{userId}/packages/search', 'app.api.reseller.v21.packages:getByAttributesAction');
$resellerApi->get('/user/{userId}/packages/{id}', 'app.api.reseller.v21.packages:getByIdAction');
$resellerApi->post('/user/{userId}/packages', 'app.api.reseller.v21.packages:add2Action');
$resellerApi->put('/user/{userId}/packages/{id}', 'app.api.reseller.v21.packages:updateById')->assert('id', '\d+');
$resellerApi->put('/user/{userId}/packages/search', 'app.api.reseller.v21.packages:updateByAttributesAction');
$resellerApi->delete('/user/{userId}/packages/{id}', 'app.api.reseller.v21.packages:deleteByIdAction')->assert('id', '\d+');
$resellerApi->delete('/user/{userId}/packages/search', 'app.api.reseller.v21.packages:deleteByAttributesAction');

// Ports
$resellerApi->post('/user/{userId}/ports/preserve', 'app.api.reseller.v21.portsIPv4:setPreservedPortsAction'); // my
$resellerApi->get('/user/{userId}/ports/ipv4', 'app.api.reseller.v21.portsIPv4:getAction');
$resellerApi->get('/user/{userId}/ports/ipv4/allocation', 'app.api.reseller.v21.portsIPv4:getAllocationAction');
$resellerApi->post('/user/{userId}/ports/ipv4/allocation', 'app.api.reseller.v21.portsIPv4:setAllocationAction');
$resellerApi->post('/user/{userId}/ports/ipv4/{id}/rotationTime', 'app.api.reseller.v21.portsIPv4:setRotationTimeAction');
$resellerApi->get('/user/{userId}/ports/ipv4/replacements', 'app.api.reseller.v21.portsIPv4:getAvailableReplacementsAction');
$resellerApi->post('/user/{userId}/ports/ipv4/replacements/{ip}', 'app.api.reseller.v21.portsIPv4:setPendingReplaceAction');
$resellerApi->post('/user/{userId}/ports/ipv4/replacements', 'app.api.reseller.v21.portsIPv4:setPendingReplaceMultipleAction');
$resellerApi->get('/user/{userId}/ports/ipv6/all', 'app.api.reseller.v21.portsIPv6:getAllAction');

// Misc
$resellerApi->get('/misc/proxy/locations/{userId}', 'app.api.reseller.v21.misc:getLocationsAvailabilityAction');
$resellerApi->get('/misc/proxy/types', 'app.api.reseller.v21.misc:getProxyTypesAllowedAction');

// OTP
$resellerApi->get('/user/{userKey}/mta/isWhitelisted', 'app.api.reseller.v21.mta:isUserWhitelisted');
$resellerApi->get('/user/{userKey}/mta/ip/isTrusted', 'app.api.reseller.v21.mta:isIpTrusted');
$resellerApi->post('/user/{userKey}/mta/ip', 'app.api.reseller.v21.mta:upsertUserIp');
$resellerApi->delete('/user/{userKey}/mta/ip', 'app.api.reseller.v21.mta:deleteUserIp');
$resellerApi->delete('/user/{userKey}/mta/ip/deleteAll', 'app.api.reseller.v21.mta:deleteAllUserIps');
$resellerApi->post('/user/{userKey}/mta/otp/{type}/decrement', 'app.api.reseller.v21.mta:decrementOtpAttempts');
$resellerApi->get('/user/{userKey}/mta/otp/{type}/isExist', 'app.api.reseller.v21.mta:isOtpGenerated');
$resellerApi->get('/user/{userKey}/mta/otp/{type}', 'app.api.reseller.v21.mta:getOtp');
$resellerApi->post('/user/{userKey}/mta/otp/{type}', 'app.api.reseller.v21.mta:storeOtp');
$resellerApi->delete('/user/{userKey}/mta/otp/{type}', 'app.api.reseller.v21.mta:deleteOtp');
