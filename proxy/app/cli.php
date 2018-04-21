<?php

use Common\Events\Emitter;

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = DEBUG;
require __DIR__ . '/bootstrap.config.php';

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'dbs.options' => array (
        'reseller' => array(
            'driver' => 'pdo_mysql',
            'host' => RS_DB_HOST,
            'dbname' => RS_DB_NAME,
            'user' => RS_DB_USER,
            'password' => RS_DB_PASS,
            'charset' => 'utf8'
        ),
        'proxy' => array(
            'driver' => 'pdo_mysql',
            'host' => PR_DB_HOST,
            'dbname' => PR_DB_NAME,
            'user' => PR_DB_USER,
            'password' => PR_DB_PASS,
            'charset' => 'utf8'
        ),
        'proxy_unbuffered' => [
            'driver' => 'pdo_mysql',
            'host' => PR_DB_HOST,
            'dbname' => PR_DB_NAME,
            'user' => PR_DB_USER,
            'password' => PR_DB_PASS,
            'charset' => 'utf8',
            'driverOptions' => [
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
            ]
        ],
        'amember' => array(
            'driver' => 'pdo_mysql',
            'host' => PR_DB_HOST,
            'dbname' => 'banditim_amember',
            'user' => PR_DB_USER,
            'password' => PR_DB_PASS,
            'charset' => 'utf8'
        )
    )
));

require_once __DIR__ . '/bootstrap_am.php';

$app['app.process_payment'] = $app->share(
	function () use($app) {
		return new \Reseller\Crons\ResellerProcessPayment($app);
	}
);

$app['app.charge_account'] = $app->share(
	function () use($app) {
		return new \Reseller\Crons\ResellerChargeAccount($app);
	}
);

$app['events'] = function() use ($app) {
    $emitter = Emitter::getInstance();
    $emitter->setApplication($app);

    return $emitter;
};

$app['request_context']->setHost(MAIN_HOST);
$app['request_context']->setBaseUrl('/new');

return $app;