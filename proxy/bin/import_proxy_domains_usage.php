<?php

/**
 * To pass IPs redirect stream to this php script, like:
 * cat parsed.log | sort | uniq -c | sort | php bin/import_proxy_domains_usage.php 2017-04-10
 */

$app = require_once __DIR__ . '/../app/cli.php';

$linesProcessed = 0;
$reportCountTime = 10000;
$format = [
    'counter' => 0,
    'domain' => 1,
    'proxy_ip' => 2
];
$types = [
    'facebook'   => '^([a-z0-9\-_\.]+\.)?facebook\.[a-z]{2,5}(\.[a-z]{2,5})?$',
    'google'     => '^([a-z0-9\-_\.]+\.)?google\.[a-z]{2,5}(\.[a-z]{2,5})?$',
    'spotify'    => '^([a-z0-9\-_\.]+\.)?spotify\.(com)?$',
    'ebay'       => '^([a-z0-9\-_\.]+\.)?ebay\.[a-z]{2,5}(\.[a-z]{2,5})?$',
    'amazon'     => '^([a-z0-9\-_\.]+\.)?amazon\.[a-z]{2,5}$',
    'soundcloud' => '^([a-z0-9\-_\.]+\.)?soundcloud\.(com)$',
];

if (empty($argv[1])) {
    die('Date is needed (1st argument)');
}
$date = $argv[1];
if (!strtotime($date)) {
    die("Date \"$date\" is invalid!");
}

while ($line = fgets(STDIN)) {
    if (!$line = trim($line)) {
        continue;
    }

    $line = trim($line);
    $data = explode(' ', $line);
    if (count($format) != count($data)) {
        echo "Skipping line \"$line\"" . PHP_EOL;
        continue;
    }

    $data = [
        'counter'  => $data[ $format['counter'] ],
        'domain'   => $data[ $format['domain'] ],
        'proxy_ip' => $data[ $format['proxy_ip'] ],
    ];

    $linesProcessed++;

    $app['dbs']['proxy']->executeQuery('INSERT INTO proxies_ipv4_usage
      (proxy_ip, domain, date, counter) VALUES (:ip, :domain, :date, :count)
      ON DUPLICATE KEY UPDATE counter = counter + :count', [
        'ip'     => $data[ 'proxy_ip' ],
        'domain' => $data[ 'domain' ],
        'date'   => $date,
        'count'  => $data[ 'counter' ],
    ]);

//    $type = false;
//    foreach ($types as $typeId => $domain) {
//        if (preg_match("~$domain~", $data['domain'])) {
//            $type = $typeId;
//            break;
//        }
//    }

//    if ($type) {
//        $app['dbs']['proxy']->executeQuery('INSERT INTO proxies_ipv4_usage_selected
//      (proxy_ip, type, domain, date, counter) VALUES (:ip, :type, :domain, :date, :count)
//      ON DUPLICATE KEY UPDATE counter = counter + :count', [
//            'ip'     => $data[ 'proxy_ip' ],
//            'type'   => $type,
//            'domain' => $data[ 'domain' ],
//            'date'   => $date,
//            'count'  => $data[ 'counter' ],
//        ]);
//    }

    if (0 == $linesProcessed % $reportCountTime) {
        echo "$linesProcessed processed..." . PHP_EOL;
    }
}

if (!$linesProcessed) {
    die('No input or no lines have been processed');
}

echo "$linesProcessed processed... Done!" . PHP_EOL;