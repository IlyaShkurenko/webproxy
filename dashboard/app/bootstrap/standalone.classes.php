<?php

use Proxy\Controller;
use Silex\Application;
use Proxy\Util\TFA;
use Proxy\Integrations\WHMCSPluginAuth;
use Proxy\Integrations\Amember;

/** @var Application $app */

foreach ([
    // Controllers
    'app.main_controller' => Controller\MainController::class,
    'app.admin_controller' => Controller\AdminController::class ? Controller\AdminController::class : false,
    'app.migration_controller' => class_exists(Controller\MigrationController::class) ? Controller\MigrationController::class : false,

    // Other
    'integration.whmcs.plugin.auth' => WHMCSPluginAuth::class,
    'integration.amember.api' => Amember::class,
    'session.tfa' => TFA::class
] as $key => $class) {
    if (!class_exists($class)) {
        continue;
    }

    $app[$key] = function () use ($app, $class) {
        return new $class($app);
    };
}