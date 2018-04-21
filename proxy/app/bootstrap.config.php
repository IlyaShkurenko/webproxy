<?php

foreach ([
    // Generic
    'logs.path' => __DIR__ . '/../logs',

    // Proxy system
    'proxy.auth.login.characters' => 'a-z0-9\._',

    // Dashboard
    'integration.whmcs.path'     => WHMCS_PATH,
    'integration.whmcs.username' => WHMCS_USERNAME,
    'integration.whmcs.password' => WHMCS_PASSWORD,
    'integration.whmcs.client'   => WHMCS_CLIENT,
    'integration.whmcs.secret'   => WHMCS_SECRET,
] as $path => $value) {
    $app["config.$path"] = $value;
}