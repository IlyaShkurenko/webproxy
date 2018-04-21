<?php

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use WHMCS\Module\Blazing\Proxy\Seller\Controller\APIController;
use WHMCS\Module\Blazing\Proxy\Seller\Controller\RequestArgumentsValueResolver;
use WHMCS\Module\Blazing\Proxy\Seller\Exception\UserFriendlyException;
use WHMCS\Module\Blazing\Proxy\Seller\Logger;

require_once __DIR__ . '/bootstrap.php';

// Catch request
$request = Request::createFromGlobals();

// Handle request
$controller = new APIController();
$return = $controller->handleRequestAction($request);
$response = new JsonResponse($return);

// Send response
$response->send();