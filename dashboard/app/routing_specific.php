<?php

/** @var Application $app */

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernel;

// External urls
if (isset($app['config.amember.loginUrl'])) {
    $app->get($app['config.amember.loginUrl'], function() {})->bind('loginAmember');
}
// Private urls
$app->get('/dashboard/contact/', 'app.dashboard_controller:contact')->bind('contact');
$app->match('/checkout/migrate/amember-whmcs/{packageId}', 'app.migration_controller:migratePackageFromAmemberToWhmcs')
    ->bind('checkout_migrate_amember_whmcs');
$app->match('/admin/add', 'app.admin_controller:add')->bind('admin_add');
$app->match('/admin/stats', 'app.admin_controller:stats')->bind('admin_stats');

// Seo routes-aliases
$routes = include __DIR__  . '/routing_seo.php';
if (!empty($routes)) {
    foreach ($routes as $from => $to) {
        $app->get($from, function(Application $app, Request $request) use ($to) {
            $subrequest = Request::create(
                $request->getUriForPath($to),
                $request->getMethod(),
                array_merge($request->query->all(), $request->request->all()),
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all()
            );

            $app['request.master'] = $request;

            return $app->handle($subrequest, HttpKernel::MASTER_REQUEST, false);
        })->bind('subrequest_' . md5($from));
    }
}