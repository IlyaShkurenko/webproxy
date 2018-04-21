<?php

require __DIR__ . '/bootstrap.php';

try {
    $data = EasyAuth\getRequestData();
    EasyAuth\assertHasData($data, 'callbackUrl', 'Wrong request: no callbackUrl is passed');

    $response = !empty($_SESSION[ 'uid' ]) ? ['id' => $_SESSION[ 'uid' ]] : [];

    $callbackUrl = $data['callbackUrl'];
    $callbackUrl .= (false === strpos($callbackUrl, '?')) ? '?' : '&';
    $callbackUrl .= http_build_query($response);

    $hookResult = run_hook('ClientAuthOnCheck', array_merge($data, $response, ['callbackUrl' => $callbackUrl]));
    if ($hookResult and !empty($hookResult[0]['redirectTo'])) {
        $callbackUrlOverride = $hookResult[0]['redirectTo'];
        $callbackUrlOverride .= (false === strpos($callbackUrlOverride, '?')) ? '?' : '&';
        $callbackUrl .= http_build_query(['origCallbackUrl' => $callbackUrl]);
    }

    header("Location: $callbackUrl", true, 302);
}
catch (\Exception $e) {
    EasyAuth\handleException($e);
}