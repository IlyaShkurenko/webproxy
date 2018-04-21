<?php

use Silex\Application;
use Silex\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Yaml\Yaml;

/** @var Application $app */

if (is_file(__DIR__ . '/routing_specific.php')) {
    require __DIR__ . '/routing_specific.php';
}

require __DIR__ . '/routing_common.php';

// Adjust routing with themes
if ($file = $app['theme.dirs'][0] . '/_meta/routing.yml' and is_file($file)) {
    $data = Yaml::parse(file_get_contents($file));
    foreach ($data as $id => $newUrl) {
        if ($route = $app['controllers']->getRoute($id)) {
            /** @var Route $route */
            $route->setPath($newUrl);
        }
    }
}

$app->get('/', function() use ($app) {
    return new RedirectResponse($app[ 'url_generator' ]->generate('index', [], UrlGenerator::ABSOLUTE_URL), 301);
})->bind('root');