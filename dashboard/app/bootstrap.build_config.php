<?php

/** @var Application $app */

use Silex\Application;

if (is_file(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

$config = [];

// Merge all needed files
$config = array_merge($config, require __DIR__ . '/config/common.php');
foreach ([
    'standalone',
    'view',
    'legacy',
    'obsolete'
] as $file) {
    if (is_file(__DIR__ . "/config/$file.php")) {
        /** @noinspection PhpIncludeInspection */
        $config = array_merge($config, require __DIR__ . "/config/$file.php");
    }
}

foreach ($config as $to => $value) {
    if (!isset($app["config.$to"])) {
        $app["config.$to"] = $value;
    }
}