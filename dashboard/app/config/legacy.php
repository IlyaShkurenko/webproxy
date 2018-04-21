<?php

if (!defined('AMEMBER_URL')) {
    return [];
}

return [
    'whmcs.ignore_on_auth_groups' => (defined('WHMCS_IGNORE_AUTH_PASSWD_GROUPS') and 'WHMCS_IGNORE_AUTH_PASSWD_GROUPS') ?
        (explode(',', WHMCS_IGNORE_AUTH_PASSWD_GROUPS)) : [],

    'amember.url'          => AMEMBER_URL,
    'amember.url.api'      => AMEMBER_URL_API,
    'amember.loginUrl'     => AMEMBER_LOGIN_URL,
    'amember.apiKey'       => AMEMBER_API_KEY,
    'amember.url.authForm' => AMEMBER_LOGIN_FORM,

    'proxyOldUrls' => PROXY_OLD_URLS,

    'db.proxy.user' => PR_DB_USER,
    'db.proxy.pass' => PR_DB_PASS,
    'db.proxy.name' => PR_DB_NAME,
    'db.proxy.host' => PR_DB_HOST,
];