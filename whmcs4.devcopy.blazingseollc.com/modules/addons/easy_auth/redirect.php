<?php

use Symfony\Component\HttpFoundation\Request;
use WHMCS\Module\Framework\Helper;

require __DIR__ . '/bootstrap.php';

try {
    $data = EasyAuth\getRequestData(true);
    EasyAuth\assertHasData($data, 'username', 'Wrong request: no username passed');
    EasyAuth\assertHasData($data, 'password', 'Wrong request: no password passed');
    EasyAuth\assertHasData($data, 'url.success', 'Wrong request: no success redirect url passed');
}
catch (\Exception $e) {
    EasyAuth\handleException($e);
}

$failure = !empty($_REQUEST['incorrect']) and 'true' == $_REQUEST['incorrect'];
if (empty($_GET['noHook'])) {
    $request = Request::createFromGlobals();
    $uri = $request->getSchemeAndHttpHost() . $request->getBaseUrl() .
        rtrim($request->getPathInfo(), '/') . '?' . $request->getQueryString() . '&noHook=1';
    /** @noinspection PhpUndefinedVariableInspection */
    $user = Helper::conn()->selectOne('SELECT id FROM tblclients WHERE email = ?', [$data['username']]);

    $hookResult = run_hook('ClientAuthOnSignIn', array_merge([
        'id' => !empty($user['id']) ? $user['id'] : false,
        'username' => $data['username'],
        'callbackUrl' => $uri
    ], ['callbackUrl' => $uri]));
    if ($hookResult and !empty($hookResult[0]['redirectTo'])) {
        $callbackUrl = $hookResult[0]['redirectTo'];
        $callbackUrl .= (false === strpos($callbackUrl, '?')) ? '?' : '&';
        $callbackUrl .= http_build_query(['origCallbackUrl' => $uri]);

        header("Location: $callbackUrl", true, 302);
    }
}

// Error
if ($failure) {
    if (empty($data['url']['fail'])) {
	    die("<h2><center>{$data['text']['error']}</center></h2>");
    }
    else {
        header("Location: {$data['url']['fail']}", true, 302);
    }
}

// Success
if (!empty($data['url']['success'])) {
    header("Location: {$data['url']['success']}", true, 302);
}
else {
    header("Location: clientarea.php", true, 302);
}