<?php

use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../vendor/autoload.php';

$request = Request::createFromGlobals();
$baseUrl = $request->getBasePath();
define('URL_SYMLINK_RESELLER', '/reseller' == $baseUrl);
define('URL_SYMLINK_API', '/api' == $baseUrl);
define('URL_SYMLINK_PROXY', in_array($baseUrl, ['/new', '/endpoint', ''], true));

$app = require __DIR__ . '/../app/bootstrap.php';

if ($app instanceof Silex\Application) {
    $app->run($request);
} else {
    header('Content-Type: text/plain', true, 500);
    echo 'Failed to initialize application.';
}