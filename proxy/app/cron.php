<?php

use Silex\Application;

require_once __DIR__.'/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();
require __DIR__ . '/bootstrap.config.php';