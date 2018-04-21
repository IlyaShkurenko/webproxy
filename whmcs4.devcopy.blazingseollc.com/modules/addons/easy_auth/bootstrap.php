<?php

namespace EasyAuth;

use Axelarge\ArrayTools\Arr;
use ErrorException;
use WHMCS\Module\Framework\Addon;

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

function getRequestData($useScheme = false)
{
    /** @var Addon $module */
    $module = require __DIR__ . '/easy_auth.php';
    if (!$module->getConfig('version')) {
        throw new ErrorException('Easy Auth is not enabled');
    }

    $data = Arr::getOrElse($_REQUEST, 'data');

    if ($data) {
        $data = base64_decode($data);

        if ($data) {
            try {
                $data = json_decode($data, true);
            }
            catch (\Exception $e) {

            }
        }
    }

    if (!$data) {
        throw new ErrorException('Wrong request: no data passed');
    }

    if ($useScheme) {
        $scheme = $module->getOriginalConfig('requestScheme');

        return array_replace_recursive($scheme, $data);
    }

    return $data;
}

function assertHasData(array $data, $key, $error)
{
    if (!Arr::getNested($data, $key)) {
        throw new ErrorException($error);
    }
}

function handleException(\Exception $e)
{
    die('Error: ' . $e->getMessage());
}

