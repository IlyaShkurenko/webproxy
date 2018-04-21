<?php

use Gears\Arrays;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

return function(Application $app, Request $request) {
    $viewConfig = [];
    foreach ([
        'layout.include_scripts' => true,
        'layout.include_scripts_app' => true,
        'layout.append_content' => false,
        'layout.type' => 'default'
    ] as $key => $default) {
        $keyFull = "config.view.$key";
        Arrays::set($viewConfig, $key, isset($app[$keyFull]) ? $app["config.view.$key"] : $default);
    }

    $app['twig']->addGlobal('USER', $app['session']->get('user'));
    $app['twig']->addGlobal('CONFIG_VIEW', $viewConfig);
    $app['twig']->addGlobal('CURRENT_ROUTE', strtolower($request->get('_route')));

    $app['config.view.asset_relative_path'] = 'proxy-dashboard';
};