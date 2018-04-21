<?php 

// ### Framework

define("DEBUG", true);
define("MAIN_HOST", (!empty($_SERVER['HTTP_HOST']) and $_SERVER['HTTP_HOST'] != 'localhost') ?
    $_SERVER['HTTP_HOST'] : 'blazingseollc.com');

// ### Database

// Main
define("PR_DB_USER", "root");
define("PR_DB_PASS", "");
define("PR_DB_NAME", "blazing_proxy");
define("PR_DB_HOST", "localhost");
// Reseller
define("RS_DB_USER", "root");
define("RS_DB_PASS", "");
define("RS_DB_NAME", "blazing_reseller");
define("RS_DB_HOST", "localhost");

// ### Reseller, amember

//define("AM_LITE_PATH", __DIR__ . "/deps/amember/library/Am/Lite.php");
define('AM_DUMMY', true);
define("AMEMBER_ACCESS_ID", 271);
define("AMEMBER_CREDIT_ID", 272);

// ### Dashboard

// # WHMCS
define("WHMCS_PATH", "https://blazing.hosting");
// API
define("WHMCS_USERNAME", "api");
define("WHMCS_PASSWORD", "abc123");
// OAUTH
define("WHMCS_CLIENT", "BLAZING-PROXY.UnamNrivCX/YblajSFHr3Q==");
define("WHMCS_SECRET", "l1Fnwvv97ptZPgjvEWMp3kn5ZloI3hbKueQzntBqXSHYxvCXVH4QpeTJRzRZqlLgM3ZP0NhMP6+NzQW1Houc1w==");