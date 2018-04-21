<?php

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use WHMCS\Module\Blazing\Proxy\Seller\Controller\CallbackController;
use WHMCS\Module\Blazing\Proxy\Seller\Controller\RequestArgumentsValueResolver;
use WHMCS\Module\Blazing\Proxy\Seller\Exception\UserFriendlyException;
use WHMCS\Module\Blazing\Proxy\Seller\Logger;

require_once __DIR__ . '/bootstrap.php';

// Catch request
$request = Request::createFromGlobals();
$callbackUrl = $request->get('callbackUrl');
if (!$callbackUrl) {
    throw new ErrorException("No \"callbackUrl\" is passed");
}

// Handle request
$controller = new CallbackController();
$return = $controller->handleRequestAction($request);
$callbackUrl .= (false === strpos($callbackUrl, '?')) ? '?' : '&';
$callbackUrl .= http_build_query($return);

// Send response
$response = new RedirectResponse($callbackUrl);
$response->send();