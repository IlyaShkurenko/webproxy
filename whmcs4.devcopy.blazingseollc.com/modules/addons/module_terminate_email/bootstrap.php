<?php

global $templates_compiledir;
$whmcsInitialized = !empty($templates_compiledir);

// Load WHMCS autoloader before (static files issue)
if (!$whmcsInitialized) {
    require_once __DIR__ . '/../../../vendor/autoload.php';
}

require_once __DIR__ . '/vendor/autoload.php';

// Load WHMCS
if (!$whmcsInitialized) {
    require_once __DIR__ . '/../../../init.php';
}