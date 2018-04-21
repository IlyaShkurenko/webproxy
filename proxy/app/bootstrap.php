<?php

use Application\ControllerResolver;
use Common\Events\Emitter;
use Application\TwigExtension;
use Proxy\Integrations\WHMCS;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../vendor/autoload.php';

Symfony\Component\Debug\ErrorHandler::register();

$app = new Silex\Application();

$app['debug'] = DEBUG;
require __DIR__ . '/bootstrap.config.php';

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app['resolver'] = $app->share(function () use ($app) {
    return new ControllerResolver($app, $app['logger']);
});
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
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

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/views',
));

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    if (!isset($app['request.master'])) {
        $twig->addExtension(new TwigExtension($app));
    }

	if (!empty($app['app.am_management'])) {
	    $twig->addGlobal('AM_LOGIN_URL', $app['app.am_management']->getLoginURL());
	    $twig->addGlobal('AM_LOGOUT_URL', $app['app.am_management']->getLogoutURL());
	}
	// Workaround for templates
	else {
		$twig->addGlobal('AM_LOGIN_URL', '');
		$twig->addGlobal('AM_LOGOUT_URL', '');
	}

    $twig->addGlobal('MAIN_URL', $app['url_generator']->generate('web_dir', [], UrlGenerator::ABSOLUTE_URL));

    return $twig;
}));

$app['app.user_management'] = $app->share(
	function () use ($app) {
		return new Blazing\UserManagement($app);
	}
);

$app['events'] = function() use ($app) {
    $emitter = Emitter::getInstance();
    $emitter->setApplication($app);

    return $emitter;
};

// Bootstrap classes
foreach ([
    // Controllers
    'app.dashboard_controller'           => Reseller\Controller\DashboardController::class,
    'app.api_proxy_v1_user'              => Reseller\Controller\ApiProxyUserV1Controller::class,
    'app.api_proxy_v1_info'              => Reseller\Controller\ApiProxyInfoV1Controller::class,
    'app.api_proxy_v1_reseller'          => Reseller\Controller\ApiProxyResellerV1Controller::class,
    'app.proxy.plist'                    => Proxy\Controller\PlistController::class,
    'app.proxy.api'                      => Proxy\Controller\APIController::class,
    'app.proxy.not_found'                => Application\NotFoundController::class,
    'app.proxy.sysInfo'                  => Proxy\Controller\SysInfoController::class,

    // Other
    'integration.whmcs.api'              => WHMCS::class
] as $key => $controller) {
	$app[$key] = $app->share(
		function () use ($app, $controller) {
			return new $controller($app);
		}
	);
}

$app->error(function (\Exception $e, $code ) use ($app) {
    if (!isset($app['config.no_error_handling']) or empty($app['config.no_error_handling'])) {
        return $app->json([
            'error' => true,
            'status' => 'error',
            'message' => $e->getMessage()
        ], 400);
    }
});

require_once __DIR__  . '/routing.php';

$app->before(function(Request $request, Silex\Application $app) {
    foreach ([
        'proxy_dashboard_' => __DIR__ . '/bootstrap/proxy.dashboard.php'
    ] as $alias => $path) {
        if (0 === strpos($request->get('_route'), $alias)) {
            $callback = require $path;
            $callback($app, $request);
            break;
        }
    }
});

return $app;

