<?php

use WHMCS\Module\Blazing\Proxy\Seller\RedirectTracker;

require_once __DIR__ . '/vendor/autoload.php';

if (empty($_GET['url'])) {
    die('Bad request');
}

$url = $_GET['url'];
$data = !empty($_GET['track_data']) ? $_GET['track_data'] : [];

if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_PATH_REQUIRED)) {
    die('Bad url: ' . $url);
}

if ($data) {
    RedirectTracker::trackData($data, 'invoice');
}
header("Location: $url", true, 302);