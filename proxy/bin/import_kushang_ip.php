<?php

/**
 * To pass IPs redirect stream to this php script, like:
 * gunzip ipaddresses_1097.txt.gz -c | php bin/import_kushang_ip.php
 */

$app = require_once __DIR__ . '/../app/cli.php';

$linesProcessed = 0;
$reportCountTime = 10000;

while ($line = fgets(STDIN)) {
    if (!$line = trim($line)) {
        continue;
    }

    $app['dbs']['proxy']->executeQuery('INSERT INTO assigner_ipv4_kushang_blacklist_ip 
      (ip) VALUES (?)
      ON DUPLICATE KEY UPDATE ip = ip', [$line]);

    $linesProcessed++;

    if (0 == $linesProcessed % $reportCountTime) {
        echo "$linesProcessed processed..." . PHP_EOL;
    }
}

if (!$linesProcessed) {
    die('No input or no lines have been processed');
}

echo "$linesProcessed processed... Done!" . PHP_EOL;