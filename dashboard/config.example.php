<?php

define("DEBUG", true);

// API
define('API_HOST', 'blazing.proxy.api');
define('API_TOKEN', 'YourApiKey');

// WHMCS

define("WHMCS_PATH", "https://blazing.whmcs/");
define("WHMCS_API_PATH", "https://blazing.whmcs/");
// API
define("WHMCS_USERNAME", "api");
define("WHMCS_PASSWORD", "123321");
// OAUTH
define("WHMCS_CLIENT", "BLAZING-PROXY.UnamNrivCX/YblajSFHr3Q==");
define("WHMCS_SECRET", "l1Fnwvv97ptZPgjvEWMp3kn5ZloI3hbKueQzntBqXSHYxvCXVH4QpeTJRzRZqlLgM3ZP0NhMP6+NzQW1Houc1w==");

// Proxy System
define("PROXY_URL", "http://" . API_HOST);

// Captcha
define('CAPTCHA_KEY', '123');
define('CAPTCHA_SECRET', '123');
define('CAPTCHA_PAGES', '');

// Misc
define('LINK_TOS', 'http://google.com/tos');
define('LINK_AUP', 'http://google.com/aup');
define('LINK_PP', 'https://google.com/privacy-policy.html');
define('LINK_NO_REFUND', 'http://google.com/no-refund');
define('LINK_HELP', 'http://billing.blazingseollc.com/hosting/submitticket.php?step=2&amp;deptid=4');
define('THEME', 'default');

// MTA
define('MTA_COOKIE_NAME', false);
define('MTA_OTP_EXPIRATION', .25 * 60 * 60);
define('MTA_TOKEN_EXPIRATION', 365 * 24 * 60 * 60);
define('MTA_TOKEN_SECRET_MASK', '{userId}.neil.secret');
define('MTA_SIGN_SECRET', '.sign.secret');
define('MTA_ATTEMPTS', 5);

// ### Legacy

// Database
define("PR_DB_USER", "root");
define("PR_DB_PASS", "");
define("PR_DB_NAME", "blazing_proxy");
define("PR_DB_HOST", "localhost");

// API
define('AMEMBER_URL', 'http://blazing.proxy.old');
define('AMEMBER_LOGIN_URL', 'http://blazing.proxy.old/');
define('AMEMBER_URL_API', 'http://blazing.proxy.old/amember/api');
define('AMEMBER_API_KEY', '123');
define('AMEMBER_LOGIN_FORM', 'http://blazing.proxy.old/amember/login');

// Auth
define('PROXY_OLD_URLS', 'http://blazing.proxy.old/dashboard');
define('WHMCS_IGNORE_AUTH_PASSWD_GROUPS', '2');

// ### Deprecated
define("PW_PORT", 4444);
define("IP_PORT", 3128);
define("USER_TYPE", "BL");
define("ROTATE_IP", "1.1.1.1");
define("WHMCS_PROXY_CATEGORY", 1);
define("WHMCS_PAYMENT_MODULE", 'paypal');
