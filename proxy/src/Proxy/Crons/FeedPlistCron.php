<?php

namespace Proxy\Crons;

use Application\Helper;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\FeedBoxFactory;

/**
 * Class FeedPlistCron based on "plist.update.php"
 *
 * @package Reseller\Crons
 */
class FeedPlistCron extends AbstractDefaultSettingsCron
{
    protected $settings = [
        'rowsPerPush' => 100000,
        'oldMethod' => false
    ];

    public function getQueryParts()
    {
        $allCategories = ':categoryDedicated, :categorySemi, :categoryMapple, :categorySneaker, :categoryKushang, ' .
            ':categorySupreme, :categoryBlock';

        return [
            'args' => [
                'userTypeClient' => Port::TYPE_CLIENT,
                'userTypeReseller' => Port::TYPE_RESELLER,
                'userTypeInternal' => Port::TYPE_INTERNAL,

                'categoryDedicated' => Port::toOldCategory(Port::CATEGORY_DEDICATED),
                'categorySemi' => Port::CATEGORY_SEMI_DEDICATED,
                'categorySneaker' => Port::CATEGORY_SNEAKER,
                'categorySupreme' => Port::CATEGORY_SUPREME,
                'categoryMapple' => Port::CATEGORY_MAPPLE,
                'categoryKushang' => Port::CATEGORY_KUSHANG,
                'categoryBlock' => Port::CATEGORY_BLOCK
            ],
            'sources' => [
                'customer' => "
                    FROM user_ports up
                    INNER JOIN proxies_ipv4 p ON p.id = up.proxy_ip
                    INNER JOIN proxy_users u ON u.id = up.user_id
                        AND up.user_type IN (:userTypeClient, :userTypeInternal) AND up.category IN($allCategories)",
                'customer_no_users' => "
                    FROM user_ports up
                    INNER JOIN proxies_ipv4 p ON p.id = up.proxy_ip",
                'customer_users' => "INNER JOIN proxy_users u ON u.id = up.user_id
                        AND up.user_type IN (:userTypeClient, :userTypeInternal) AND up.category IN($allCategories)",
                'reseller' => "
                    FROM user_ports up
                    INNER JOIN proxies_ipv4 p ON p.id = up.proxy_ip
                    INNER JOIN reseller_users u ON u.id = up.user_id
                        AND up.user_type = :userTypeReseller AND up.category IN($allCategories)"
            ],
            'joinUserIp' => [
                'customer' => 'INNER JOIN user_ips i ON i.user_id = up.user_id AND i.user_type = up.user_type',
                'reseller' => 'INNER JOIN user_ips i ON i.user_id = up.user_id AND i.user_type = up.user_type'
            ],
            'ipActive' => '(p.active = 1 or p.new = 1)',
            'format' => [
                'ip' => [
                    'customer' => "preferred_format = 'IP'",
                    'reseller' => "auth_type = 'ip'"
                ],
                'pwd' => [
                    'customer' => "preferred_format = 'PW'",
                    'reseller' => "auth_type = 'pw'"
                ]
            ],
            'allCategories' => $allCategories,
        ];
    }

    public function run()
    {
        $feedBox = FeedBoxFactory::build();
        $feedBox->setRowsPerPush($this->getSetting('rowsPerPush'));

        $queryPart = $this->getQueryParts();
        $queryArgs = $queryPart['args'];

        // Feed timestamps
        if ($this->getSetting('oldMethod')) {
            $this->oldMethod($queryPart, $queryArgs, $feedBox);
        }
        else {
            $this->newMethod($queryPart, $queryArgs, $feedBox);
        }

        return true;
    }

    public function oldMethod(array $queryPart, array $queryArgs, $feedBox)
    {
        /** @var \Proxy\FeedBox\AbstractFeedBox|\Proxy\FeedBox\AbstractFeedBoxPartials $feedBox */

        $this->debug('Fetching timestamps');
        $stmt = $this->getConn()->query("
            SELECT max(preferred_format_update) as last_rotated
            FROM proxy_users
        ");
        $lastUpdated = strtotime($stmt->fetchColumn());
        $timestamps = [];

        $stmt = $this->getConn()->executeQuery("
            SELECT max(last_rotated) as last_rotated, 'ip' as type
            FROM (

                SELECT GREATEST(COALESCE(max(up.last_rotated), 0), COALESCE(max(up.time_assigned), 0), COALESCE(max(up.time_updated), 0), COALESCE(max(i.date_created), 0), COALESCE(max(u.preferred_format_update), 0)) as last_rotated
                {$queryPart['sources']['customer']}
                {$queryPart['joinUserIp']['customer']}
                WHERE {$queryPart['format']['ip']['customer']} AND {$queryPart['ipActive']}

                UNION ALL

                SELECT GREATEST(COALESCE(max(up.last_rotated), 0), COALESCE(max(up.time_updated), 0), COALESCE(max(i.date_created),0), COALESCE(max(u.updated), 0)) as last_rotated
                {$queryPart['sources']['reseller']}
                {$queryPart['joinUserIp']['reseller']}
                WHERE {$queryPart['format']['ip']['reseller']} AND {$queryPart['ipActive']}

            ) as ipmax

            UNION ALL

            SELECT max(last_rotated) as last_rotated, 'pwd' as type
            FROM (
                SELECT GREATEST(COALESCE(max(up.last_rotated), 0), COALESCE(max(up.time_updated), 0), COALESCE(max(up.time_assigned), 0), COALESCE(max(u.preferred_format_update), 0)) as last_rotated
                {$queryPart['sources']['customer']}
                WHERE {$queryPart['format']['pwd']['customer']} AND {$queryPart['ipActive']}

                UNION ALL

                SELECT GREATEST(COALESCE(max(up.last_rotated), 0), COALESCE(max(up.time_updated), 0), COALESCE(max(u.updated), 0)) as last_rotated
                {$queryPart['sources']['reseller']}
                WHERE {$queryPart['format']['pwd']['reseller']} AND {$queryPart['ipActive']}
            ) as pwmax", $queryArgs);

        while($row = $stmt->fetch()) {
            $userLastUpdated = strtotime($row['last_rotated']);
            $maxLastUpdated = ($userLastUpdated > $lastUpdated) ? $userLastUpdated : $lastUpdated;

            $this->debug(strtoupper($row['type']) . " list updated on " . date('Y-m-d H:i:s', $maxLastUpdated), [
                'type'                   => $row[ 'type' ],
                'preferredFormatUpdated' => date('Y-m-d H:i:s', $lastUpdated),
                'userUpdated'            => date('Y-m-d H:i:s', $userLastUpdated),
                'maxUpdated'             => date('Y-m-d H:i:s', $maxLastUpdated)
            ]);
            $timestamps[$row['type']] = $maxLastUpdated;
            // $feedBox->push("{$row['type']}", $maxLastRotated);
        }

        // Feed IP

        if ($feedBox->pull('timestamp.ip', 0) < $timestamps['ip']) {
            $this->debug('Generating IP list', [
                'last' => date('Y-m-d H:i:s', $timestamps[ 'ip' ]),
                'prev' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.ip', 0)),
            ]);

            $stmt = $this->getConn('unbuffered')->executeQuery("
                SELECT s.ip, p.ip as serverIp, i.ip as userIp, IF(up.category = 'sneaker', 'sneaker', null) AS whitelist
                {$queryPart['sources']['customer']}
                {$queryPart['joinUserIp']['customer']}
                INNER JOIN proxy_source s ON s.id = p.source_id
                WHERE {$queryPart['format']['ip']['customer']} AND {$queryPart['ipActive']}
    
                UNION ALL
    
                SELECT s.ip, p.ip as serverIp, i.ip as userIp, IF(up.category = 'sneaker', 'sneaker', null) AS whitelist
                {$queryPart['sources']['reseller']}
                {$queryPart['joinUserIp']['reseller']}
                INNER JOIN proxy_source s ON s.id = p.source_id
                WHERE {$queryPart['format']['ip']['reseller']} AND {$queryPart['ipActive']}
    
                UNION ALL
    
                SELECT s.ip, p.ip as serverIp, i.ip as user_ip, null AS whitelist
                FROM proxy_users pu
                INNER JOIN user_ips i ON pu.id = i.user_id
                INNER JOIN proxies_ipv4 p
                INNER JOIN proxy_source s ON s.id = p.source_id
                WHERE pu.email = 'proxy@splicertech.com'
                GROUP BY i.ip, p.ip
    
                ORDER BY null
            ", $queryArgs);

            $this->debug('IP list fetched');

            $i = 0;
            $feedBox->setRowsPerPush($this->getSetting('rowsPerPush') / 5);
            while ($row = $stmt->fetch()) {
                $key = "ip.{$row['ip']}";
                $feedBox->startPartialQueue($key);
                $feedBox->pushPartial($key, $row);
                $i++;
            }
            $feedBox->endAllPartialQueues();
            $feedBox->push("timestamp.ip", $timestamps[ 'ip' ]);

            $this->log("IP list flushed, $i records");
        }
        else {
            $this->debug("IP list has not been generated due no update", [
                'last' => date('Y-m-d H:i:s', $timestamps[ 'ip' ]),
                'prev' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.ip', 0)),
            ]);
        }

        // Feed PWD

        if ($feedBox->pull('timestamp.pwd', 0) < $timestamps['pwd']) {
            $this->debug('Generating PWD list', [
                'last' => date('Y-m-d H:i:s', $timestamps[ 'pwd' ]),
                'prev' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.pwd', 0)),
            ]);

            $stmt = $this->getConn('unbuffered')->executeQuery("
            SELECT p.ip, ps.ip as source_ip, u.login as login, u.email, u.api_key, 
              IF(up.category = 'sneaker', 1, null) AS is_sneaker
            {$queryPart['sources']['customer']}
            INNER JOIN proxy_source ps ON ps.id = p.source_id
            WHERE {$queryPart['format']['pwd']['customer']} AND {$queryPart['ipActive']}

            UNION ALL

            SELECT p.ip, ps.ip as source_ip, u.username as login, '' as email, u.api_key, 
              IF(up.category = 'sneaker', 1, null) AS is_sneaker
            {$queryPart['sources']['reseller']}
            INNER JOIN proxy_source ps ON ps.id = p.source_id
            WHERE {$queryPart['format']['pwd']['reseller']} AND {$queryPart['ipActive']}
            
            UNION ALL

            SELECT p.ip, ps.ip as source_ip, u.email, u.login as login, u.api_key, 
              1 AS is_sneaker
            FROM proxy_users u
            INNER JOIN proxies_ipv4 p
            INNER JOIN proxy_source ps ON ps.id = p.source_id
            WHERE u.email = 'neil.emeigh@gmail.com' AND p.ip IN ('179.61.209.232', '179.61.211.70', '181.214.0.71')
        ", $queryArgs);

            $this->debug('PWD list fetched');

            $feedBox->startPartialQueue('pwd');
            $i = 0;
            while ($row = $stmt->fetch()) {
                if (!$row[ 'login' ]) {
                    $this->debug("Empty login skipped", ['row' => $row]);
                    continue;
                }

                foreach (explode(',', $row['login']) as $login) {
                    $login = Helper::sanitizeLogin($login, $this->getApp());
                    if (!$login) {
                        $this->debug("Empty login skipped", ['row' => $row]);
                        continue;
                    }

                    $data = [
                        'serverIp' => $row[ 'ip' ],
                        'login'    => $login,
                        'secret'   => $row[ 'api_key' ],
                        'sourceIp' => $row[ 'source_ip' ]
                    ];
                    $whitelist = [];
                    if (!empty($row[ 'is_sneaker' ])) {
                        $whitelist[] = 'sneaker';
                    }
                    if ($whitelist) {
                        $data[ 'white' ] = $whitelist;
                    }

                    $feedBox->pushPartial('pwd', $data);
                }
                $i++;
            }
            $feedBox->endPartialQueue('pwd');
            $feedBox->push("timestamp.pwd", $timestamps[ 'pwd' ]);

            $this->log("PWD list flushed, $i records");
        }
        else {
            $this->debug("PWD list has not been generated due no update", [
                'last' => date('Y-m-d H:i:s', $timestamps[ 'pwd' ]),
                'prev' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.pwd', 0)),
            ]);
        }
    }

    public function newMethod(array $queryPart, array $queryArgs, $feedBox)
    {
        /** @var \Proxy\FeedBox\AbstractFeedBox|\Proxy\FeedBox\AbstractFeedBoxPartials $feedBox */

        $this->debug('Fetching timestamps');
        $stmt = $this->getConn()->query("
            SELECT max(preferred_format_update) as last_rotated
            FROM proxy_users
        ");
        $lastUserUpdated = strtotime($stmt->fetchColumn());
        $timestamps = [
            'ip'  => 0,
            'pwd' => 0
        ];

        // Feed PWD

        $lastUpdated = $this->getConn()->executeQuery("
                SELECT max(last_rotated)
                FROM (
    
                    SELECT GREATEST(COALESCE(max(up.last_rotated), 0), COALESCE(max(up.time_updated), 0), COALESCE(max(up.time_assigned), 0), COALESCE(max(u.preferred_format_update), 0)) as last_rotated
                    {$queryPart['sources']['customer']}
                    WHERE {$queryPart['format']['pwd']['customer']}
    
                    UNION ALL
    
                    SELECT GREATEST(COALESCE(max(up.last_rotated), 0), COALESCE(max(up.time_updated), 0), COALESCE(max(u.updated), 0)) as last_rotated
                    {$queryPart['sources']['reseller']}
                    WHERE {$queryPart['format']['pwd']['reseller']}
                ) as t
            ", $queryArgs)->fetchColumn();
        if ($lastUpdated) {

            // Calculate the last timestamp
            $lastUpdated = strtotime($lastUpdated);
            $maxLastUpdated = ($lastUpdated > $lastUserUpdated) ? $lastUpdated : $lastUserUpdated;
            if ($timestamps['pwd'] < $maxLastUpdated) {
                $timestamps['pwd'] = $maxLastUpdated;
            }

            $this->debug("PWD list updated on " . date('Y-m-d H:i:s', $maxLastUpdated), [
                'preferredFormatUpdated' => date('Y-m-d H:i:s', $lastUserUpdated),
                'userUpdated'            => date('Y-m-d H:i:s', $lastUpdated),
                'maxUpdated'             => date('Y-m-d H:i:s', $maxLastUpdated)
            ]);
            if ($feedBox->pull('timestamp.pwd', 0) < $maxLastUpdated) {
                $this->debug('Generating PWD list', [
                    'last' => date('Y-m-d H:i:s', $maxLastUpdated),
                    'prev' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.pwd', 0)),
                ]);

                $stmt = $this->getConn('unbuffered')->executeQuery("
                SELECT p.ip, ps.ip as source_ip, u.login as login, u.email, u.api_key, 
                  IF(up.category = 'sneaker', 1, null) AS is_sneaker
                {$queryPart['sources']['customer']}
                INNER JOIN proxy_source ps ON ps.id = p.source_id
                WHERE {$queryPart['format']['pwd']['customer']}
    
                UNION ALL
    
                SELECT p.ip, ps.ip as source_ip, u.username as login, '' as email, u.api_key, 
                  IF(up.category = 'sneaker', 1, null) AS is_sneaker
                {$queryPart['sources']['reseller']}
                INNER JOIN proxy_source ps ON ps.id = p.source_id
                WHERE {$queryPart['format']['pwd']['reseller']}
                
                UNION ALL
    
                SELECT p.ip, ps.ip as source_ip, u.email, u.login as login, u.api_key, 
                  1 AS is_sneaker
                FROM proxy_users u
                INNER JOIN proxies_ipv4 p
                INNER JOIN proxy_source ps ON ps.id = p.source_id
                WHERE u.email = 'neil.emeigh@gmail.com' AND p.ip IN ('179.61.209.232', '179.61.211.70', '181.214.0.71')
                
                ORDER BY null
            ", $queryArgs);

                $this->debug('PWD list fetched');

                $feedBox->startPartialQueue('pwd');
                $i = 0;
                while ($row = $stmt->fetch()) {
                    if (!$row[ 'login' ]) {
                        $this->debug("Empty login skipped", ['row' => $row]);
                        continue;
                    }

                    foreach (explode(',', $row['login']) as $login) {
                        $login = Helper::sanitizeLogin($login, $this->getApp());
                        if (!$login) {
                            $this->debug("Empty login skipped", ['row' => $row]);
                            continue;
                        }

                        $data = [
                            'serverIp' => $row[ 'ip' ],
                            'login'    => $login,
                            'secret'   => $row[ 'api_key' ],
                            'sourceIp' => $row[ 'source_ip' ]
                        ];
                        $whitelist = [];
                        if (!empty($row[ 'is_sneaker' ])) {
                            $whitelist[] = 'sneaker';
                        }
                        if ($whitelist) {
                            $data[ 'white' ] = $whitelist;
                        }

                        $feedBox->pushPartial('pwd', $data);
                    }
                    $i++;
                }
                $feedBox->endPartialQueue('pwd');

                $this->log("PWD list flushed, $i records");

                $this->debug("PWD lists updated on " . date('Y-m-d H:i:s', $timestamps[ 'pwd' ]), [
                    'before' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.pwd', 0))
                ]);
                $feedBox->push("timestamp.pwd", $timestamps[ 'pwd' ]);
            }
            else {
                $this->debug("PWD list has not been generated due no update", [
                    'last' => date('Y-m-d H:i:s', $timestamps[ 'pwd' ]),
                    'prev' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.pwd', 0)),
                ]);
            }
        }

        // Feed IP

        $sources = $this->getConn()->executeQuery('SELECT * FROM proxy_source')->fetchAll();
        foreach ($sources as $i => $source) {
            $lastUpdated = $this->getConn()->executeQuery("
                SELECT max(last_rotated)
                FROM (
    
                    SELECT GREATEST(COALESCE(max(up.last_rotated), 0), COALESCE(max(up.time_assigned), 0), COALESCE(max(up.time_updated), 0), COALESCE(max(i.date_created), 0)) as last_rotated
                    {$queryPart['sources']['customer_no_users']}
                    {$queryPart['joinUserIp']['customer']}
                    WHERE p.source_id = :proxySource
    
                    UNION ALL
                    
                    SELECT COALESCE(max(u.preferred_format_update), 0) as last_rotated
                    {$queryPart['sources']['customer']}                    
                    WHERE {$queryPart['format']['ip']['customer']} AND p.source_id = :proxySource
                    
                    UNION ALL
    
                    SELECT GREATEST(COALESCE(max(up.last_rotated), 0), COALESCE(max(up.time_updated), 0), COALESCE(max(i.date_created),0), COALESCE(max(u.updated), 0)) as last_rotated
                    {$queryPart['sources']['reseller']}
                    {$queryPart['joinUserIp']['reseller']}
                    WHERE {$queryPart['format']['ip']['reseller']} AND p.source_id = :proxySource
                    
                    UNION ALL
                    
                    SELECT GREATEST(COALESCE(max(date_added), 0), COALESCE(max(date_updated), 0)) as last_rotated
                    FROM proxies_ipv4
                    WHERE source_id = :proxySource    
                ) as t
                ORDER BY null
            ", array_merge($queryArgs, ['proxySource' => $source['id']]))->fetchColumn();

            // No IPs on the server
            if (!$lastUpdated) {
                continue;
            }

            // Calculate the last timestamp
            $lastUpdated = strtotime($lastUpdated);
            $maxLastUpdated = ($lastUpdated > $lastUserUpdated) ? $lastUpdated : $lastUserUpdated;
            if ($timestamps['ip'] < $maxLastUpdated) {
                $timestamps['ip'] = $maxLastUpdated;
            }

//            $this->debug("IP list of source \"{$source['name']}\" (\"{$source['ip']}\") " .
//                "updated on " . date('Y-m-d H:i:s', $maxLastUpdated), [
//                'preferredFormatUpdated' => date('Y-m-d H:i:s', $lastUserUpdated),
//                'userUpdated'            => date('Y-m-d H:i:s', $lastUpdated),
//                'maxUpdated'             => date('Y-m-d H:i:s', $maxLastUpdated),
//                'storedTimestmp' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.ip', 0))
//            ]);

            // The list has no update
            if ($feedBox->pull('timestamp.ip', 0) >= $maxLastUpdated) {
                $this->debug("IP list of source \"{$source['name']}\" (\"{$source['ip']}\") has not been generated" .
                    " due no update" .
                    ' ' . ($i + 1) . '/' . count($sources), [
                    'last' => date('Y-m-d H:i:s', $maxLastUpdated),
                    'prev' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.ip', 0)),

                    'preferredFormatUpdated' => date('Y-m-d H:i:s', $lastUserUpdated),
                    'userUpdated'            => date('Y-m-d H:i:s', $lastUpdated),
                    'maxUpdated'             => date('Y-m-d H:i:s', $maxLastUpdated),
                    'storedTimestmp' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.ip', 0))
                ]);

                 continue;
            }

            $this->debug("Generating IP list of source \"{$source['name']}\" (\"{$source['ip']}\") " .
                ($i + 1) . '/' . count($sources), [
                'last' => date('Y-m-d H:i:s', $maxLastUpdated),
                'prev' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.ip', 0)),

                'preferredFormatUpdated' => date('Y-m-d H:i:s', $lastUserUpdated),
                'userUpdated'            => date('Y-m-d H:i:s', $lastUpdated),
                'maxUpdated'             => date('Y-m-d H:i:s', $maxLastUpdated),
                'storedTimestmp' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.ip', 0))
            ]);

            $stmt = $this->getConn('unbuffered')->executeQuery("
                SELECT s.ip, p.ip as serverIp, i.ip as userIp, IF(up.category = 'sneaker', 'sneaker', null) AS whitelist
                {$queryPart['sources']['customer_no_users']}
                {$queryPart['joinUserIp']['customer']}
                INNER JOIN proxy_source s ON s.id = p.source_id
                WHERE up.user_id IN (SELECT id FROM proxy_users u WHERE {$queryPart['format']['ip']['customer']}) 
                AND p.source_id = :proxySource
    
                UNION ALL
    
                SELECT s.ip, p.ip as serverIp, i.ip as userIp, IF(up.category = 'sneaker', 'sneaker', null) AS whitelist
                {$queryPart['sources']['reseller']}
                {$queryPart['joinUserIp']['reseller']}
                INNER JOIN proxy_source s ON s.id = p.source_id
                WHERE {$queryPart['format']['ip']['reseller']} AND p.source_id = :proxySource
    
                UNION ALL
    
                SELECT s.ip, p.ip as serverIp, i.ip as user_ip, null AS whitelist
                FROM proxy_users pu
                INNER JOIN user_ips i ON pu.id = i.user_id
                INNER JOIN proxies_ipv4 p
                INNER JOIN proxy_source s ON s.id = p.source_id
                WHERE pu.email = 'proxy@splicertech.com' AND p.source_id = :proxySource
                GROUP BY i.ip, p.ip
    
                ORDER BY null
            ", array_merge($queryArgs, ['proxySource' => $source['id']]));

            $this->debug("IP list of source \"{$source['name']}\" (\"{$source['ip']}\") is fetched, flushing");

            $i = 0;
            $feedBox->setRowsPerPush($this->getSetting('rowsPerPush') / 5);
            while ($row = $stmt->fetch()) {
                $key = "ip.{$row['ip']}";
                $feedBox->startPartialQueue($key);
                $feedBox->pushPartial($key, $row);
                $i++;
            }

            $this->log("IP list of source \"{$source['name']}\" (\"{$source['ip']}\") is flushed, $i records");
        }
        $feedBox->endAllPartialQueues();
        $this->debug("IP lists updated on " . date('Y-m-d H:i:s', $timestamps[ 'ip' ]), [
            'before' => date('Y-m-d H:i:s', $feedBox->pull('timestamp.ip', 0))
        ]);
        $feedBox->push("timestamp.ip", $timestamps[ 'ip' ]);
    }
}
