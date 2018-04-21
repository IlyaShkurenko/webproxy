<?php

use Silex\Application;

/** @var Application $app */
/** @var Application $api */

foreach ([
    'app.api.reseller.v2.misc'  => ProxyReseller\Controller\ApiV20\MiscController::class,
    'app.api.reseller.v2.user'  => ProxyReseller\Controller\ApiV20\UserController::class,
    'app.api.reseller.v2.userManagement'  => ProxyReseller\Controller\ApiV20\UserManagementController::class,
    'app.api.reseller.v2.packages'  => ProxyReseller\Controller\ApiV20\PackagesController::class,
    'app.api.reseller.v2.ports'  => ProxyReseller\Controller\ApiV20\PortsController::class,
    'app.api.reseller.v2.mta'  => ProxyReseller\Controller\ApiV20\MtaController::class,

] as $key => $controller) {
    $app[$key] = $app->share(
        function () use ($app, $controller) {
            return new $controller($app);
        }
    );
}

$api->mount('/reseller/v2.0', $resellerApi = $app['controllers_factory']);

// User management
$resellerApi->get('/users/find/byBillingId/{source}/{id}', 'app.api.reseller.v2.userManagement:getUserIdByBillingAction');
$resellerApi->get('/users/find/byLoginOrEmail/{loginOrEmail}', 'app.api.reseller.v2.userManagement:findUserByLoginOrEmail');
$resellerApi->post('/users/upsert/{source}/{id}', 'app.api.reseller.v2.userManagement:upsertUserAction');

// User
$resellerApi->get('/user/{userId}/details', 'app.api.reseller.v2.user:getDetailsAction')->bind('apiv2.user.details');
$resellerApi->post('/user/{userId}/details/sneakerLocation', 'app.api.reseller.v2.user:updateSneakerLocationAction');
$resellerApi->post('/user/{userId}/details', 'app.api.reseller.v2.user:updateSettingsAction');
$resellerApi->get('/user/{userId}/details/auth/ip', 'app.api.reseller.v2.user:getAuthIpListAction');
$resellerApi->post('/user/{userId}/details/auth/ip/{ip}', 'app.api.reseller.v2.user:addAuthIpAction');
$resellerApi->delete('/user/{userId}/details/auth/ip/{ipId}', 'app.api.reseller.v2.user:deleteAuthIpAction');

// Packages
$resellerApi->get('/user/{userId}/packages', 'app.api.reseller.v2.packages:getAllAction');
$resellerApi->delete('/user/{userId}/packages', 'app.api.reseller.v2.packages:deleteAllAction');
$resellerApi->get('/user/{userId}/packages/find', 'app.api.reseller.v2.packages:getAction');
$resellerApi->post('/user/{userId}/packages/{source}', 'app.api.reseller.v2.packages:addAction');
$resellerApi->put('/user/{userId}/packages/{source}', 'app.api.reseller.v2.packages:updateAction');
$resellerApi->delete('/user/{userId}/packages/{source}', 'app.api.reseller.v2.packages:deleteAction');

// Ports
$resellerApi->get('/user/{userId}/ports', 'app.api.reseller.v2.ports:getAction');
$resellerApi->get('/user/{userId}/ports/allocation', 'app.api.reseller.v2.ports:getAllocationAction');
$resellerApi->post('/user/{userId}/ports/allocation', 'app.api.reseller.v2.ports:setAllocationAction');
$resellerApi->post('/user/{userId}/ports/{id}/rotationTime', 'app.api.reseller.v2.ports:setRotationTimeAction');
$resellerApi->get('/user/{userId}/ports/replacements', 'app.api.reseller.v2.ports:getAvailableReplacementsAction');
$resellerApi->post('/user/{userId}/ports/replacements/{ip}', 'app.api.reseller.v2.ports:setPendingReplaceAction');
$resellerApi->post('/user/{userId}/ports/replacements', 'app.api.reseller.v2.ports:setPendingReplaceMultipleAction');

// Misc
$resellerApi->get('/misc/proxy/locations/{userId}', 'app.api.reseller.v2.misc:getLocationsAvailabilityAction');
$resellerApi->get('/misc/proxy/types', 'app.api.reseller.v2.misc:getProxyTypesAllowedAction');

// OTP
$resellerApi->get('/user/{userId}/mta/isWhitelisted', 'app.api.reseller.v2.mta:isUserWhitelisted');
$resellerApi->get('/user/{userId}/mta/ip/isTrusted', 'app.api.reseller.v2.mta:isIpTrusted');
$resellerApi->post('/user/{userId}/mta/ip', 'app.api.reseller.v2.mta:upsertUserIp');
$resellerApi->post('/user/{userId}/mta/otp/decrement/{type}', 'app.api.reseller.v2.mta:decrementOtpAttempts');
$resellerApi->get('/user/{userId}/mta/otp/isExists/{type}', 'app.api.reseller.v2.mta:isOtpGenerated');
$resellerApi->get('/user/{userId}/mta/otp/{type}', 'app.api.reseller.v2.mta:getOtp');
$resellerApi->post('/user/{userId}/mta/otp/{type}', 'app.api.reseller.v2.mta:storeOtp');
$resellerApi->delete('/user/{userId}/mta/otp/{type}', 'app.api.reseller.v2.mta:deleteOtp');
