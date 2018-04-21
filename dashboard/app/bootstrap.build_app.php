<?php

use Symfony\Component\Debug\ErrorHandler;

ErrorHandler::register();

$app = new Silex\Application();

return $app;