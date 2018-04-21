<?php

require_once __DIR__ . '/bootstrap.php';

error_reporting(E_ALL ^ E_NOTICE);

$app = new \Symfony\Component\Console\Application('GetResponseExport', '0.1');

$app->add(new WHMCS\Module\Blazing\Export\GetResponse\Cli\ExportCommand());

$app->setDefaultCommand('getresponse:export');

$app->run(
    new Symfony\Component\Console\Input\ArgvInput(),
    new Symfony\Component\Console\Output\ConsoleOutput()
);
