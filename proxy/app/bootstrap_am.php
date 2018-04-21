<?php

/** @var \Silex\Application $app */

if (!$app) {
	return;
}

$dummy = (defined('AM_DUMMY') and AM_DUMMY);

// AMember enabled in usual way
if (!$dummy and defined('AM_LITE_PATH')) {

	require_once AM_LITE_PATH;

	$app[ 'app.am_management' ] = $app->share(
		function () {
			return new \Blazing\AmManagement();
		}
	);
}
elseif ($dummy) {
	$app[ 'app.am_management' ] = $app->share(
		function () use ($app) {
			return new \Blazing\DummyAmManagement($app);
		}
	);

	$app['app.dashboard_am_dummy'] = $app->share(
		function () use ($app) {
			return new \Reseller\Controller\DummyAmController($app);
		}
	);
}