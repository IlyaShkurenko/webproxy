<?php

if (!defined('WHMCS_PATH') or isset($app['config.mode.standalone'])) {
    return [];
}

return [
    'whmcs.path'     => WHMCS_PATH,
    'whmcs.path.api' => defined('WHMCS_API_PATH') ? WHMCS_API_PATH : WHMCS_PATH,
    'whmcs.username' => WHMCS_USERNAME,
    'whmcs.password' => WHMCS_PASSWORD,
    'whmcs.client'   => WHMCS_CLIENT,
    'whmcs.secret'   => WHMCS_SECRET,

    'proxyUrl' => PROXY_URL,

    'api.host'  => API_HOST,
    'api.token' => API_TOKEN,

    'mta.email' => defined('MTA_COOKIE_NAME') ? true : false,
    'mta.config.cookieName' => defined('MTA_COOKIE_NAME') ? MTA_COOKIE_NAME : null,
    'mta.config.otpExpiration' => defined('MTA_OTP_EXPIRATION') ? MTA_OTP_EXPIRATION : null,
    'mta.config.tokenExpiration' => defined('MTA_TOKEN_EXPIRATION') ? MTA_TOKEN_EXPIRATION : null,
    'mta.config.tokenSecretMask' => defined('MTA_TOKEN_SECRET_MASK') ? MTA_TOKEN_SECRET_MASK : null,
    'mta.config.signSecret' => defined('MTA_SIGN_SECRET') ? MTA_SIGN_SECRET : null,
    'mta.config.attempts' => defined('MTA_ATTEMPTS') ? MTA_ATTEMPTS : null,
];