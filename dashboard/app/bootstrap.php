<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Support for built-in mode
if (!isset($app) or !$app instanceof Silex\Application) {
    $app = require __DIR__ . '/bootstrap.build_app.php';
}
require __DIR__ . '/bootstrap.build_config.php';

$app['debug'] = $app['config.debug'];
$app['theme.dirs'] = function() use ($app) {
    return array_map(function ($dir) {
        return realpath($dir);
    }, array_filter(array_merge(
        'default' != $app[ 'config.view.theme' ] ?
            [__DIR__ . '/../src/views/' . $app[ 'config.view.theme' ]] : [],
        [__DIR__ . '/../src/views/default']
    ), function ($dir) {
        return is_dir($dir);
    }));
};

use Axelarge\ArrayTools\Arr;
use Blazing\Logger\Logger;
use Blazing\Reseller\Api;
use Gears\Arrays;
use Proxy\Controller;
use Proxy\Database\Helper;
use Proxy\Integrations\ProxySystem;
use Proxy\Integrations\WHMCS;
use Proxy\Integrations\WHMCSPlugin;
use Proxy\Integrations\WHMCSPluginRequestHandler;
use Proxy\Integrations\WHMCSRequestHandler;
use Proxy\Silex\ControllerCollection;
use Proxy\TwigExtension;
use Proxy\User;
use Proxy\Util\Util;
use Proxy\Util\VarsFlashBag;
use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

if (!isset($app['session'])) {
    $app->register(new Silex\Provider\SessionServiceProvider(), [
        'session.storage.options' => [
            'name' => 'DASHBOARD_BLAZING',
        ],
    ]);
}
$app['session']->registerBag(new VarsFlashBag());
$app['session.user'] = function($app) {
    return new User($app);
};
if (!isset($app['logs']) and isset($app['config.log.path'])) {
    $logger = Logger::createRotatingFileLogger($app['config.log.path']);
    $logger->configureAppEnvProcessor(__DIR__ . '/../composer.json');
    $app['logs'] = $logger;
}
$app['api'] = function($app) {
    $configuration = new Api\Configuration();

    $configuration->setApiToken($app['config.api.token']);
    if (isset($app['config.api.host'])) {
        $configuration->setHost($app['config.api.host']);
    }
    if (isset($app['config.api.protocol'])) {
        $configuration->setProtocol($app['config.api.protocol']);
    }
    if (isset($app['config.api.url'])) {
        $configuration->setUrl($app['config.api.url']);
    }
    if (!empty($app['logs'])) {
        $configuration->setLogger($app['logs']);
    }

    return new Api\Api($configuration);
};

$app->register(new Silex\Provider\ServiceControllerServiceProvider());
if (is_file(__DIR__ . '/bootstrap/standalone.db.php')) {
    require __DIR__ . '/bootstrap/standalone.db.php';
}

$app->register(new LocaleServiceProvider());
$app->register(new TranslationServiceProvider());
$app->extend('translator', function(Translator $translator) use ($app) {
    $translator->addLoader('yaml', new YamlFileLoader());

    foreach (['en'] as $locale) {
        $translator->addResource('yaml', __DIR__ . "/locales/$locale.yml", $locale);

        foreach ([$app['theme.dirs'][0]] as $dir) {
            $file = "$dir/_meta/locales/$locale.yml";
            if (is_file($file)) {
                $translator->addResource('yaml', $file, $locale);
            }
        }
    }

    return $translator;
});

/** @noinspection PhpParamsInspection */
$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => array_merge(
        // Theme roots
        $app['theme.dirs'],
        // App directory
        [__DIR__])
]);
$app['twig']->addExtension(new TwigExtension($app));
$app->before(function(Request $request, Silex\Application $app) {
    $found = false;
    foreach ($app['twig']->getExtensions() as $extension) {
        if ($extension instanceof TwigExtension) {
            $found = true;
        }
    }
    if (!$found) {
        $app['twig']->addExtension(new TwigExtension($app));
    }
    $app['twig']->addGlobal('PW_PORT', $app['config.port.pwd']);
    $app['twig']->addGlobal('IP_PORT', $app['config.port.ip']);

    $viewConfig = [];
    foreach (array_keys(require __DIR__ . '/config/view.php') as $key) {
        $keyFull = "config.$key";
        Arrays::set($viewConfig, preg_replace('~^view\.~', '', $key), isset($app[$keyFull]) ? $app["config.$key"] : null);
    }
    $app['twig']->addGlobal('CONFIG_VIEW', $viewConfig);

    // Compatibility with templates
    $app['twig']->addGlobal('USER', !$app['session.user']->isAuthorized() ? null :
        array_merge($details = $app['session.user']->getDetails(), [
            'api_key'          => $details[ 'apiKey' ],
            'sneaker_location' => $details[ 'sneakerLocation' ],
            'preferred_format' => $details[ 'authType' ],
            'admin'            => !empty($details[ 'isAdmin' ]),
        ]));

}, 40);
$app->before(function(Request $request, Silex\Application $app) {
    $app['twig']->addGlobal('LAYOUT_TYPE', $request->get('layout_type', 'default'));
});

foreach ([
    // Controllers
    'app.home_controller' => Controller\HomeController::class,
    'app.dashboard_controller' => Controller\DashboardController::class,
    'app.checkout_controller' => Controller\CheckoutController::class,
    'app.api_controller' => Controller\ApiController::class,

    // Other
    'integration.whmcs.api' => WHMCS::class,
    'integration.whmcs.api.request_handler' => WHMCSRequestHandler::class,
    'integration.whmcs.plugin' => WHMCSPlugin::class,
    'integration.whmcs.plugin.request_handler' => WHMCSPluginRequestHandler::class,
    'integration.proxy.api' => ProxySystem::class,
    'db_helper' => Helper::class
] as $key => $class) {
    if (!class_exists($class)) {
        continue;
    }

    $app[$key] = function () use ($app, $class) {
        return new $class($app);
    };
}
if (is_file(__DIR__ . '/bootstrap/standalone.classes.php')) {
    require __DIR__ . '/bootstrap/standalone.classes.php';
}

// Routing
$app['controllers_factory'] = $controllers_factory = function () use ($app, &$controllers_factory) {
    return new ControllerCollection($app['route_factory'], $app['routes_factory'], $controllers_factory);
};
include __DIR__ . '/routing.php';

// Set up logging
if (!empty($app['logs'])) {
    // userId, sessionId index
    $app->before(function(Request $request) use ($app) {
        if ($app['session.user']->isAuthorized()) {
            $app['logs']->addSharedIndex('userId', $app['session.user']->getId());
            $app['logs']->addSharedIndex('sessionId', $app['session']->getId());
        }
        else {
            if (!empty($_COOKIE['VIRTUAL_TRACK'])) {
                $id = $_COOKIE['VIRTUAL_TRACK'];
            }
            else {
                $request->attributes->set('$sessionTrack', $id = $app['logs']->getRequestUid());
            }

            $app['logs']->addSharedIndex('sessionId', "virtual-$id");
        }

        $app['logs']->addSharedIndex('action', 'Unknown');
        $app['logs']->addSharedIndex('ip', $request->getClientIp());
    }, 40);

    // Exception
    $app->on(KernelEvents::EXCEPTION, function(GetResponseForExceptionEvent $evt) use ($app) {
        $exception = $evt->getException();

        $app['logs']->err('Exception: ' . $exception->getMessage(), [
            'exception' => $exception,
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'trace'     => $exception->getTraceAsString()
        ]);
    }, 40);

    // action index
    $app->on(KernelEvents::CONTROLLER, function (FilterControllerEvent $evt) use ($app) {
        $controller = $evt->getController();

        /** @noinspection PhpParamsInspection */
        if (is_array($controller) and 2 == count($controller)) {
            $class = Util::getClassBasename($controller[ 0 ]);
            $method = str_replace('Action', '', $controller[ 1 ]);

            $app['logs']->addSharedIndex('action', "$class#$method");
        }
    });

    // Log response
    $app->on(KernelEvents::RESPONSE, function(FilterResponseEvent $evt) use ($app) {
        if ($evt->getResponse()->isNotFound()) {
            return;
        }

        $parameters = array_merge(
            Arr::filterWithKey($evt->getRequest()->attributes->all(), function($value, $key) {
                return !(in_array($key, ['_controller', '_route', '_route_params']) or is_object($value));
            }),
            $evt->getRequest()->query->all(),
            $evt->getRequest()->request->all()
        );

        $response = '[html]';
        if ('application/json' == $evt->getResponse()->headers->get('content-type')) {
            $response = $evt->getResponse()->getContent();
        }

        $action = $app['logs']->getSharedIndex('action');

        if (!$evt->getResponse()->isRedirection()) {
            $app['logs']->info("$action: OK", ['parameters' => $parameters, 'response' => $response, 'request' => [
                'path'   => $evt->getRequest()->getPathInfo(),
                'method' => $evt->getRequest()->getMethod()
            ]]);
        }
        else {
            $location = $evt->getResponse()->headers->get('location');
            $app['logs']->info("$action: redirect to \"$location\"", ['parameters' => $parameters]);
        }

        // Handle virtual session id
        if ($evt->getRequest()->attributes->has('$sessionTrack')) {
            $evt->getResponse()->headers->setCookie(
                new Cookie('VIRTUAL_TRACK', $evt->getRequest()->attributes->get('$sessionTrack'), time() + 365 * 24 * 60 * 60));
        }
    });
}

$app->on(KernelEvents::EXCEPTION, function(GetResponseForExceptionEvent $evt) use ($app) {
    try {
        $errorController = new Controller\ErrorController($app);
        $response = $errorController->handleException($evt->getException());

        if ($response) {
            $evt->setResponse($response);
            $evt->stopPropagation();
        }
    }
    catch (\Exception $e) {}
});

$app->before(function(Request $request, Silex\Application $app) {
    $route = $request->get('_route');
    $routes = include __DIR__ . '/routing_aliases.php';
    if (!empty($routes[$route])) {
        $route = $routes[$route];
    }

    $route = strtolower($route);
    $app['twig']->addGlobal('CURRENT_ROUTE', $route);
    $app['twig']->addGlobal('MAIN_URL', $app['url_generator']->generate('root', [], UrlGenerator::ABSOLUTE_URL));
});

$app->before(function(Request $request, Silex\Application $app) {
    $allowedRoutes = [
        // Initial page
        'index',
        // Login
        'login', 'loginDetermine', 'loginTypeCheckLogin', 'loginType', 'relogin', 'oauth_code', 'tfa',
        // Callbacks
        'callback_whmcs',
        // Quick buy
        'checkout_total', 'quick_buy', 'quick_buy_empty', 'do_quick_buy', 'quick_buy_check_email', 'checkout_promocode',
        'quick_buy_continue_tfa',
        // Other pages
        'contact', 'bridge_proxy_list'
    ];

    if (!$app['session.user']->isAuthorized() and $request->get('_route') and !in_array($request->get('_route'), $allowedRoutes)
        and 0 !== strpos($request->get('_route'), 'subrequest_')) {
        if (!empty($app['logs'])) {
            $app['logs']->debug('Redirecting unauthorized user to index page', ['route' => $request->get('_route')]);
        }

        return new RedirectResponse($app['url_generator']->generate('index'));
    }
});

$app->before(function(Request $request, Silex\Application $app) {
    if ($app[ 'session.user' ]->getTFA()) {
        $app[ 'session.user' ]->getTFA()->setRequest($request);
    }

    if ($app['session.user']->isAuthorized()) {
        if ($request->get('_route') == 'dashboard' and $request->get('paid')) {
            $app['session']->getBag('vars')->set('paid', 1);

            return new RedirectResponse($app['url_generator']->generate('dashboard'));
        }
    }

    if (!empty($app['config.mta.email']) and $app[ 'session.user' ]->getTFA() and
        !in_array($request->get('_route'), [
            // Initial page
            'index',
            // Login
            'login', 'loginDetermine', 'loginTypeCheckLogin', 'loginType', 'relogin', 'logout', 'oauth_code', 'tfa',
            // // Quick buy
            'checkout_total', 'quick_buy', 'quick_buy_empty', 'do_quick_buy', 'quick_buy_check_email', 'checkout_promocode',
            'quick_buy_continue_tfa',
            // Other pages
            'contact', 'bridge_proxy_list'
        ])) {
        if (
            // Existent user
            (
                $app['session.user']->isAuthorized() and
                !$app[ 'session.user' ]->getTFA()->isValidated($app[ 'session.user' ]->getId())
            ) or
            // A new user (has no account yet)
            (
                $app['session']->has('tfa.requiredVerification')
            )
        ) {
            return new RedirectResponse($app[ 'url_generator' ]->generate('tfa'));
        }
    }
});

$app->before(function(Request $request, Silex\Application $app) {
    $app['twig']->addGlobal('HAS_PROXIES', false);
    $app['twig']->addGlobal('HAS_SNEAKER', false);
    $app['twig']->addGlobal('HAS_IPv6', false);

    /** @var User $user */
    $user = $app['session.user'];
    /** @var Api\Api $api */
    $api = $app['api'];

    if ($user->isAuthorized()) {
        $redirectRoutes = [
            // Locations
            'locations', 'save_locations', 'sneaker',
            // Checkout
            'checkout_total', 'doCheckout', 'checkout_promocode', 'checkout_process',
            // Login
            'logout', 'relogin', 'oauth_code', 'tfa'
        ];

        // Sneaker

        $sneakers = $api->ports4()->getAll('us', 'sneaker')['list'];
        if (!!$sneakers and !$user->getDetails('sneakerLocation') and !in_array($request->get('_route'), $redirectRoutes)) {
            $app['session']->set('redirect_dashboard', true);
            if (!empty($app['logs'])) {
                $app['logs']->debug('Redirect user to set sneaker location', ['userDetails' => $user->getDetails()]);
            }

            return new RedirectResponse($app['url_generator']->generate('sneaker'));
        }
        $app['twig']->addGlobal('HAS_SNEAKER', !!$sneakers);

        // IPv6 dedi/semi

        $allAllocated = true;
        $proxies = $api->ports4()->getAllocation([], ['dedicated', 'semi-3'])['list'];
        foreach ($proxies as $country => $categories) {
            foreach ($categories as $data) {
                if (!empty($data['regions'][0])) {
                    $allAllocated = false;
                    break;
                }
            }
        }
        if (!$allAllocated && !in_array($request->get('_route'), $redirectRoutes)) {
            $app['session']->set('redirect_dashboard', true);
            if (!empty($app['logs'])) {
                $app['logs']->debug('Redirect user to set proxy locations');
            }

            return new RedirectResponse($app['url_generator']->generate('locations'));
        }
        $app['twig']->addGlobal('HAS_PROXIES', !!$proxies);

        // IPv6

        if ($api->packages()->getAll(false, false, Api\Api\Entity\PackageEntity::IP_V_6)['list']) {
            $app['twig']->addGlobal('HAS_IPv6', true);
        }
    }
});

$app->before(function(Request $request, Silex\Application $app) {
    // Feature is disabled
    if (!isset($app['db'])) {
        return;
    }

    $title = '';
    $pageDescription = '';
    $keywords = '';
    $description = '';

    if (isset($app['request.master'])) {
        $request = $app['request.master'];
    }

    $row = $app['db']->fetchAssoc("
      SELECT title, description, meta_keywords, meta_description 
      FROM billing_dashboard_seo
      WHERE TRIM(BOTH '/' FROM url) = ?", [trim($request->getPathInfo(), '/')]);
    if ($row) {
        if (!empty($row['title'])) {
            $title = $row['title'];
        }
        if (!empty($row['description'])) {
            $pageDescription = $row['description'];
        }
        if (!empty($row['meta_keywords'])) {
            $keywords = $row['meta_keywords'];
        }
        if (!empty($row['meta_description'])) {
            $description = $row['meta_description'];
        }
    }

    $app['twig']->addGlobal('SEO_TITLE', $title);
    $app['twig']->addGlobal('SEO_PAGE_DESCRIPTION', $pageDescription);
    $app['twig']->addGlobal('SEO_KEYWORDS', $keywords);
    $app['twig']->addGlobal('SEO_DESCRIPTION', $description);
});

return $app;
