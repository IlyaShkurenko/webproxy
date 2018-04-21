<?php

$logname = (isset($argv[0])) ? strtolower(substr(basename($argv[0]), 0, -4)) : 'cli';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Debug\ErrorHandler;
use Proxy\Integrations\WHMCS;

ErrorHandler::register();

$app = new Silex\Application();

$app['debug'] = DEBUG;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'dbs.options' => array(
        'proxy' => array(
            'driver' => 'pdo_mysql',
            'host' => PR_DB_HOST,
            'dbname' => PR_DB_NAME,
            'user' => PR_DB_USER,
            'password' => PR_DB_PASS,
            'charset' => 'utf8'
        ),
        'reseller' => array(
            'driver' => 'pdo_mysql',
            'host' => RS_DB_HOST,
            'dbname' => RS_DB_NAME,
            'user' => RS_DB_USER,
            'password' => RS_DB_PASS,
            'charset' => 'utf8'
        )
    )
));

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/' . $logname . '.log',
    'monolog.name' => $logname
));

$app['whmcs'] = function() {
    return new WHMCS();
};

$app['assignment'] = function() use ($app) {
    return new Proxy\Database\Assignment($app);
};

return $app;