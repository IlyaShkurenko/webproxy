<?php

/**
 * To pass IPs redirect stream to this php script, like:
 * cat range.txt | php bin/import_kushang_range.php
 */

$app = require_once __DIR__ . '/../app/cli.php';

$linesProcessed = 0;
$recordsAdded = 0;
$reportCountTime = 10000;
$skipLines = 1; // headers

while ($line = fgets(STDIN)) {
    if (!$line = trim($line)) {
        continue;
    }

    $linesProcessed++;

    // Skip headers
    if ($linesProcessed <= $skipLines) {
        echo sprintf('Skipping "%s"', $line) . PHP_EOL;
        continue;
    }

    $parsed = explode(',', $line);
    // Validated data
    if (2 != count($parsed)) {
        echo sprintf('Line "%s" is invalid', $line) . PHP_EOL;
    }

    // Parsed variables
    $fromIp = $parsed[0];
    $toIp = $parsed[1];

    for ($ip = ip2long($fromIp); $ip <= ip2long($toIp); $ip++) {
        $app['dbs']['proxy']->executeQuery('INSERT INTO assigner_ipv4_kushang_blacklist_ip 
          (ip) VALUES (?)
          ON DUPLICATE KEY UPDATE ip = ip', [long2ip($ip)]);

        $recordsAdded++;

        if (0 == $recordsAdded % $reportCountTime) {
            echo "$linesProcessed lines processed, $recordsAdded records added..." . PHP_EOL;
        }
    }
}

if (!$linesProcessed) {
    die('No input or no lines have been processed');
}

echo "$linesProcessed lines processed, $recordsAdded records added... Done!" . PHP_EOL;