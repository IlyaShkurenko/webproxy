<?php

namespace Proxy\Controller;

use Application\AbstractApiController;
use Application\Helper;
use Axelarge\ArrayTools\Arr;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Proxy\Assignment\Port\IPv4\CountedPort;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\Assignment\PortAssigner;
use Proxy\Crons\FeedPlistCron;
use Proxy\FeedBoxFactory;
use ProxyReseller\Exception\ApiException;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PlistController extends AbstractApiController
{

    protected $logPath = 'api/proxy.log';

    protected $convertResponse = 'json';

    // Static proxies

    public function __construct(Application $app)
    {
        parent::__construct($app);
        if ($this->logger) {
            $this->logger->setAppName(str_replace('/all', '/api-sys', $this->logger->getAppName()));
        }
    }

    public function listAction($type, Request $request)
    {
        $key = Arr::getOrElse([
            'ip' => 'ip',
            'pwd' => 'pwd'
        ], $type);

        if (!$key) {
            return new Response("Type \"$type\" is unknown", 400);
        }

        $ip = $request->get('ip');
        if ($ip and !filter_var($ip, FILTER_VALIDATE_IP)) {
            return new Response("IP \"$ip\" is invalid", 400);
        }

        $noAcl = $request->get('no-acl');

        return $this->getTextPlainStreamResponse(function() use ($type, $key, $ip, $noAcl) {
            if ('ip' == $type and $ip) {
                $feedBox = FeedBoxFactory::build();

                while ($data = $feedBox->pullPartialRow("ip.$ip")) {
                    // To skip servers failures
                    if (!filter_var($data['userIp'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        continue;
                    }

                    $whiteList = [];
                    if (!$noAcl) {
                        $whiteList[] = 'default';
                        if (!empty($data['whitelist'])) {
                            $whiteList = array_unique(array_merge(explode(',', $data['whitelist'])));
                        }
                    }
                    echo "{$data['userIp']} {$data['serverIp']}" .
                        ($noAcl ? '' : (' ' . join(',', $whiteList))) .
                        PHP_EOL;
                }
            }
            else {
                $feedBox = FeedBoxFactory::build();
                while ($data = $feedBox->pullPartialRow($key)) {
                    // IP filtered
                    if ($ip and (empty($data['sourceIp']) or $ip != $data['sourceIp'])) {
                        continue;
                    }

                    $whiteList = [];
                    if (!$noAcl) {
                        $whiteList[] = 'default';
                        if (!empty($data['white']) and in_array('sneaker', $data['white'])) {
                            $whiteList[] = 'sneaker';
                        }
                    }

                    switch ($type) {
                        case 'ip':
                            echo "{$data['userIp']} {$data['serverIp']}" .
                                ($noAcl ? '' : (' ' . join(',', $whiteList))) .
                                PHP_EOL;
                            break;

                        case 'pwd':
                            $login = str_replace(' ', '%20', $data['login']);
                            echo "{$data['serverIp']}:$login:{$data['secret']}" .
                                ($noAcl ? '' : (':' . join(',', $whiteList))) .
                                PHP_EOL;
                            break;
                    }
                }
            }
        });
    }

    /**
     * Based on "plist.change.php"
     *
     * @return Response
     */
    public function lastRotatedTimestampAction()
    {
        $feedBox = FeedBoxFactory::build();

        return $this->getTextPlainResponse([
            'plist.ip ' . $feedBox->pull('timestamp.ip', ''),
            'plist.pw ' . $feedBox->pull('timestamp.pwd', ''),
            ''
        ]);
    }

    // Rotating proxies

    public function rotatingProxiesIpAction()
    {
        $sql = "SELECT ip.ip, ip.user_id
            FROM user_ips ip
            INNER JOIN user_ports up ON ip.user_id = up.user_id
            WHERE up.category IN (:categoryRotating, :categoryGoogle)";
        $data = $this->getConn()->fetchAll($sql, [
            'categoryRotating' => Port::toOldCategory(Port::CATEGORY_ROTATING),
            'categoryGoogle' => Port::CATEGORY_GOOGLE
        ]);

        return $this->getTextPlainResponse(json_encode($data));
    }

    public function rotatingProxiesPortsAction()
    {
        $sql = "SELECT up.id, up.user_id, up.port as user_port, p.ip, p.port as proxy_port, u.rotation_type
            FROM user_ports up
            INNER JOIN proxies_ipv4 p ON p.id = up.proxy_ip
            INNER JOIN all_users u ON u.user_id = up.user_id AND u.user_type = up.user_type
            INNER JOIN user_ips ip ON ip.user_id = up.user_id AND ip.user_type = up.user_type     
            WHERE up.category IN (:categoryRotating, :categoryGoogle)
            GROUP BY up.id
            ORDER BY NULL";

        $data = $this->getConn()->fetchAll($sql, [
            'categoryRotating' => Port::toOldCategory(Port::CATEGORY_ROTATING),
            'categoryGoogle' => Port::CATEGORY_GOOGLE
        ]);

        return $this->getTextPlainResponse(json_encode($data));
    }

    public function rotatingProxiesThreadsAction()
    {
        $sql = "SELECT up.user_id, (COUNT(*) * 10) as ports
            FROM user_ports as up
            WHERE category IN (:categoryRotating, :categoryGoogle)     
            GROUP BY up.user_id
            ORDER BY NULL";

        $data = $this->getConn()->fetchAll($sql, [
            'categoryRotating' => Port::toOldCategory(Port::CATEGORY_ROTATING),
            'categoryGoogle' => Port::CATEGORY_GOOGLE
        ]);

        return $this->getTextPlainResponse(json_encode($data));
    }

    // All proxies

    /**
     * Based on "add.php"
     *
     * @param Request $request
     * @return Response
     */
    public function sourcesRangesAction(Request $request)
    {
        // if (!$source = $request->get('source', false)) {
        //     return $this->getTextPlainResponse(null, 400);
        // }
        $source = $request->get('source', false);

        if ($source) {
            $stmt = $this->getConn()->executeQuery("SELECT p.ip, p.mask, p.block
                FROM proxies_ipv4 p
                JOIN proxy_source s ON s.id = p.source_id
                WHERE (s.ip = :source OR s.name = :source)
                AND p.ip != s.ip
                AND (p.active = 1 OR p.new = 1)
                ORDER BY INET_ATON(p.ip)",
                ['source' => $source]
            );
        }
        else {
            $stmt = $this->getConn()->executeQuery("SELECT p.ip, p.mask, p.block
                FROM proxies_ipv4 p
                JOIN proxy_source s ON s.id = p.source_id
                WHERE 
                p.ip != s.ip
                AND (p.active = 1 OR p.new = 1)
                ORDER BY INET_ATON(p.ip)"
            );
        }

        $last = 0;
        $lastBlock = '';
        $mask = '';
        $response = '';
        while ($row = $stmt->fetch()) {
            $long = ip2long($row[ 'ip' ]);

            if ($last == 0) {
                $response .= $row[ 'ip' ] . " ";
            } elseif ($long - 1 != $last or
                ($lastBlock != $row['block'] and preg_match('~/(\d+)$~', $lastBlock, $match) and $match[1] <= 24)) {
                $response .= long2ip($last) . " " . $mask . PHP_EOL . $row[ 'ip' ] . " ";
            }

            $last = $long;
            $mask = $row[ 'mask' ];
            $lastBlock = $row['block'];

            if (!$mask) {
                $mask = '255.255.255.255';
            }
        }
        $response .= long2ip($last) . " " . $mask;

        return $this->getTextPlainResponse($response);
    }

    public function getAclAction()
    {
        $list = [];

        foreach ($this->getConn()->fetchAll('SELECT list, domain FROM proxy_acl ORDER BY id') as $row) {
            $group = $row['list'];
            $domains = [];

            // Default ACL
            if ('*' == $row['domain']) {
                $group = 'default';
                $domains[] = $row['domain'];
            }
            else {
                // Common sub-domain
                if (0 === strpos($row['domain'], '*.')) {
                    // Primary domain
                    $domains[] = substr($row['domain'], strlen('*.'));
                }

                // Sub-domain or whatever set up
                $domains[] = $row['domain'];
            }

            // Convert wildcard to regexp
            foreach ($domains as $i => $domain) {
                $domain = preg_quote($domain);
                $domain = str_replace('\*', '*', $domain);

                if ('*' == $domain) {
                    $domains[$i] = "^.+$";
                }
                else {
                    $domains[$i] = '^' . str_replace('*', '[a-zA-Z0-9\_\-]+', $domain) . '$';
                }
            }

            // External format
            foreach ($domains as $domain) {
                $list[] = join(':', [$group, $domain]);
            }
        }

        return $this->getTextPlainResponse($list);
    }

    public function getAclTermsAction(Request $request)
    {
        $list = [];
        $asString = $request->get('as-string');
        $withDate = $request->get('with-date');

        $tzDefault = 'CST';
        $tzTarget = 'UTC';
        $tzMap = [
            'CST' => 'CST6CDT'
        ];

        foreach ($this->getConn()->fetchAll('
            SELECT a.list, at.from_datetime, at.to_datetime, at.timezone, a.domain, IF(ati.id IS NULL, 0, 1) as has_terms
            FROM proxy_acl a
            LEFT JOIN proxy_acl_terms at ON a.list = at.list AND at.active = 1 AND at.to_datetime >= NOW()
            LEFT JOIN proxy_acl_terms ati ON a.list = ati.list AND ati.active = 1
            GROUP BY a.list
            ORDER BY a.id, at.from_datetime'
        ) as $row) {
            $acl = $row['list'];

            // Default 00:00-23:59
            $dateFrom = (new DateTime(date('Y-m-d 00:00'), new DateTimeZone($tzTarget)))->getTimestamp();
            $dateTo = (new DateTime(date('Y-m-d 23:59'), new DateTimeZone($tzTarget)))->getTimestamp();

            // Determine timezone
            $tz = !empty($row['timezone']) ? $row['timezone'] : $tzDefault;
            // We can't throw an exception so use default values
            if (empty($tzMap[$tz])) {
                $tz = $tzDefault;
            }
            $tz = $tzMap[$tz];

            if ($row['has_terms']) {
                if (!empty($row['from_datetime'])) {
                    $time = new DateTime($row['from_datetime'], new DateTimeZone($tz));

                    // Apply if for current day
                    if ($dateFrom < $time->getTimestamp()) {
                        $dateFrom = $time->getTimestamp();
                    }
                }

                // Date due, 00:00 just like date start (zero duration)
                if (!empty($row['to_datetime'])) {
                    $time = new DateTime($row['to_datetime'], new DateTimeZone($tz));

                    if ($dateTo > $time->getTimestamp()) {
                        $dateTo = $time->getTimestamp();
                    }
                }
                else {
                    $dateTo = $dateFrom;
                }
            }

            $list[] = sprintf('%s,%s-%s,%s', $acl,
                !$asString ? $dateFrom :
                    (new DateTime(null, new DateTimeZone($tzTarget)))
                        ->setTimestamp($dateFrom)
                        ->format(!$withDate ? 'H:i' : 'Y-m-d H:i:s'),
                !$asString ? $dateTo :
                    (new DateTime(null, new DateTimeZone($tzTarget)))
                        ->setTimestamp($dateTo)
                        ->format(!$withDate ? 'H:i' : 'Y-m-d H:i:s'),
                $tzTarget
            );
        }

        return $this->getTextPlainResponse($list);
    }

    // Proxy checking

    public function getDeadProxiesAction(Request $request)
    {
        $minDeadCount = $request->get('dead-count', 3);
        $includeHeaders = $request->get('with-headers');

        $fields = [
            'p.source' => 'source',
            'ps.ip' => 'source_ip',
            'p.ip' => 'proxy_ip',
            'p.dead' => 'dead',
            'p.dead_count' => 'dead_count',
            'p.last_check' => 'last_check',
            '0' => 'curl_error_code',
            'p.last_error' => 'curl_error_text',
        ];

        $qb = $this->getConn()->createQueryBuilder();
        $qb->from('proxies_ipv4', 'p')->innerJoin('p', 'proxy_source', 'ps', 'ps.id = p.source_id');
        $qb->where(
            'p.active = 1',
            'p.dead_count >= ' . $qb->createNamedParameter($minDeadCount)
        );
        foreach ($fields as $field => $alias) {
            $qb->addSelect($field . ' as ' . $alias);
        }
        $qb->orderBy('ps.ip')->addOrderBy('p.ip');
        $stmt = $qb->execute();

        $rows = [];

        // Headers
        if ($includeHeaders) {
            $rows[] = join(',', $fields);
        }

        // Fetch rows
        while($row = $stmt->fetch()) {
            if (preg_match('~curl error (\d+)~i', $row['curl_error_text'], $match)) {
                $row['curl_error_code'] = $match[1];
            }

            $row['curl_error_text'] = '"' . $row['curl_error_text'] . '"';
            $row['last_check'] = (new DateTime())
                ->setTimestamp(strtotime($row['last_check']))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d H:i:s');

            $rows[] = join(',', $row);
        }

        return $this->getTextPlainResponse($rows);
    }

    public function emulateAuth(Request $request)
    {
        $action = $request->get('action');
        $authMethod = $request->get('auth-method', 'ip');
        $userId = $request->get('user-id', $authMethod == 'ip' ? -2 : -3);

        if (!in_array($authMethod, ['ip', 'pwd'])) {
            throw new ApiException("Auth method \"$authMethod\" is unknown, available: ip, pwd", [], null, 'BAD_ARGUMENTS');
        }

        switch ($action) {
            case 'add':
                $authIp = $request->get('auth-ip');
                $authPwd = $request->get('auth-pwd');
                $source = $request->get('source');

                // Validate parameters
                if ('ip' == $authMethod and !$authIp) {
                    throw new ApiException('"auth-ip" is required', [], null, 'BAD_ARGUMENTS');
                }
                if (!$source) {
                    throw new ApiException('"source" is required', [], null, 'BAD_ARGUMENTS');
                }

                $removedIps = Arr::pluck($this->getConn()->executeQuery(
                    "SELECT p.ip, p.id
                    FROM user_ports up
                    INNER JOIN proxies_ipv4 p ON up.proxy_ip = p.id
                    INNER JOIN proxy_source ps ON ps.id = p.source_id
                    WHERE up.user_id = :userId AND (ps.ip = :source OR ps.name = :source)",
                    ['source' => $source, 'userId' => $userId])->fetchAll(), 'ip', 'id');
                if ($removedIps) {
                    $this->getConn()->executeQuery(
                        'DELETE up
                        FROM user_ports up
                        INNER JOIN proxies_ipv4 p ON up.proxy_ip = p.id
                        INNER JOIN proxy_source ps ON ps.id = p.source_id
                        WHERE up.user_id = :userId AND (ps.ip = :source OR ps.name = :source)',
                        ['source' => $source, 'userId' => $userId]);
                }

                $ip = $this->getConn()->executeQuery(
                    "SELECT p.id, p.ip, COUNT(up.id) as cnt
                    FROM proxies_ipv4 p
                    INNER JOIN proxy_source ps ON ps.id = p.source_id
                    LEFT JOIN user_ports up ON up.proxy_ip = p.id
                    WHERE (ps.ip = :source OR ps.name = :source) AND p.dead = 0 AND p.dead_count = 0 AND p.active = 1" .
                        ($removedIps ? " AND p.id NOT IN(:removedIps)" : "") .  "
                    GROUP BY p.id
                    ORDER BY cnt ASC, RAND()
                    LIMIT 1",
                    ['source' => $source, 'removedIps' => array_keys($removedIps)],
                    ['removedIps' => Connection::PARAM_STR_ARRAY])->fetch();

                if (!$ip) {
                    throw new ApiException('No available IP is found', ['removed' => array_values($removedIps)], null, 'IP_NOT_FOUND');
                }

                // Create port and assign it by internal method
                $assigner = new PortAssigner($this->getConn());
                $result = $assigner->alignPortsCounted(
                    CountedPort::construct($userId, Port::TYPE_INTERNAL, Port::COUNTRY_US, Port::CATEGORY_DEDICATED)
                        ->setActualPortCount(0)
                        ->setTotalPortCount(1));
                $assigner->assignPortProxy($result->getAddedPorts()[0], $ip['id']);

                // Adjust auth
                $userDetails = $this->getConn()->executeQuery('SELECT * FROM proxy_users WHERE id = ?', [$userId])->fetch();
                $update = [];
                if ($userDetails) {
                    if ('ip' == $authMethod and 'IP' != $userDetails['preferred_format']) {
                        $update['preferred_format'] = 'IP';
                    }
                    elseif ('pwd' == $authMethod and 'PW' != $userDetails['preferred_format']) {
                        $update['preferred_format'] = 'PW';
                    }
                }

                // Add user ip to whitelist
                if ('ip' == $authMethod) {
                    try {
                        $this->logger->debug('Add ip to db', [
                            'user_id' => $result->getAddedPorts()[0]->getUserId(),
                            'user_type' => $result->getAddedPorts()[0]->getUserType(),
                            'ip' => $authIp
                        ]);

                        $added = $this->getConn()->executeUpdate("
                          INSERT INTO user_ips (user_id, user_type, ip) VALUES (:userId, :userType, :ip) 
                          ON DUPLICATE KEY UPDATE user_id = :userId
                        ", [
                            'userI' => $result->getAddedPorts()[0]->getUserId(),
                            'userType' => $result->getAddedPorts()[0]->getUserType(),
                            'ip' => $authIp
                        ]);

                        $this->logger->debug($added ? 'Added ip to db' : 'IP is not added to db the second time', [
                            'user_id' => $result->getAddedPorts()[0]->getUserId(),
                            'user_type' => $result->getAddedPorts()[0]->getUserType(),
                            'ip' => $authIp
                        ]);
                    }
                    catch (\Exception $e) {
                        $this->logger->warn('Error on adding to db: ' . $e->getMessage());
                    }
                }
                // Update user password and auth type
                elseif ('pwd' == $authMethod) {
                    if ($userDetails and $userDetails['api_key'] != $authPwd) {
                        $update['api_key'] = $authPwd;
                    }
                }

                if ($update) {
                    if ($update) {
                        $update['preferred_format_update'] = date('Y-m-d H:i:s');
                    }

                    $this->logger->debug('Updating proxy users', $update + ['id' => $userId]);

                    $this->getConn()->update('proxy_users', $update, ['id' => $userId]);

                    $this->logger->debug('Updated proxy users', $update + ['id' => $userId]);
                }

                return [
                    'status' => 'ok',
                    'ip'     => $ip[ 'ip' ],
                    'removed' => array_values($removedIps)
                ];
                break;

            case 'remove':
                $proxyIp = $request->get('proxy-ip');

                // Validate parameters
                if (!$proxyIp) {
                    throw new ApiException('"proxy-ip" is required', [], null, 'BAD_ARGUMENTS');
                }

                $this->logger->debug('Removing proxy ips', [
                    'proxyIp' => $proxyIp,
                    'userId' => $userId
                ]);

                $affected = $this->getConn()->executeUpdate(
                    "DELETE up FROM user_ports up
                    INNER JOIN proxies_ipv4 p ON p.id = up.proxy_ip
                    WHERE p.ip = ? AND up.user_id = ?
                ", [$proxyIp, $userId]);

                $this->logger->debug("Removed $affected proxy ips", [
                    'proxyIp' => $proxyIp,
                    'userId' => $userId
                ]);

                if (!$affected) {
                    throw new ApiException("IP \"$proxyIp\" can not be removed as it is not added", [], null, 'IP_NOT_FOUND');
                }

                // Here we would remove auth ip, but other IP-s will fail. So entire auth process can not be emulated
                // due workflow suggestion made by Oleksandr

                return [
                    'status'  => 'ok',
                    'removed' => [$proxyIp]
                ];

                break;

            default:
                throw new ApiException("Action \"$action\" is unknown, available: add, remove", [], null, 'BAD_ARGUMENTS');
                break;
        }
    }

    // Customer API

    /**
     * Based on "list.php", user proxies
     * @param Request $request
     * @return Response
     */
    public function proxiesListAction(Request $request)
    {
        $email = $request->get('email');
        $key = $request->get('key');

        $userId = $this->getConn()
            ->executeQuery('SELECT id FROM proxy_users u WHERE u.email = ? AND u.api_key = ?', [$email, $key])
            ->fetchColumn();

        if (!$userId) {
            return $this->getTextPlainResponse(null, 400);
        }

        $plist = new FeedPlistCron();
        $queryPart = $plist->getQueryParts();
        $pwPort = $plist->getSetting('pwPort');

        $sourceCustomer = str_replace(
            ['INNER JOIN proxies_ipv4', ':categorySemi'],
            ['LEFT JOIN proxies_ipv4', ':categorySemi, :categoryRotating'],
            $queryPart['sources']['customer']
        );

        $stmt = $this->getConn()->executeQuery("
            SELECT ps.server_ip, up.port as server_port, p.ip as proxy_ip, p.port as proxy_port, 
                u.api_key, u.preferred_format, up.country, up.category,
                am.login as login, 0 AS is_email
            $sourceCustomer
            INNER JOIN proxy_server ps ON ps.id = up.server_id
            INNER JOIN banditim_amember.am_user am ON u.amember_id = am.user_id
            WHERE u.id = :userId AND u.whmcs_id IS NULL
            
            UNION ALL
            
            SELECT ps.server_ip, up.port as server_port, p.ip as proxy_ip, p.port as proxy_port, 
                u.api_key, u.preferred_format, up.country, up.category,
                u.email as login, 1 AS is_email
            $sourceCustomer
            LEFT JOIN proxy_server ps ON ps.id = up.server_id            
            WHERE u.id = :userId AND u.whmcs_id IS NOT NULL
        ", array_merge($queryPart[ 'args' ], [
            'userId'           => $userId,
            'categoryRotating' => Port::toOldCategory(Port::CATEGORY_ROTATING)
        ]));

        $response = [];
        while ($row = $stmt->fetch()) {
            if ($row[ 'is_email' ]) {
                $row[ 'login' ] = Helper::generateLogin($row[ 'login' ]);
            }

            if ($row[ 'category' ] == 'rotate') {
                $response[] = $row[ 'server_ip' ] . ":" . $row[ 'server_port' ];
            } else {
                if ($row[ 'preferred_format' ] == 'PW') {
                    $response[] = $row[ 'proxy_ip' ] . ":" . $pwPort . ":" . $row[ 'login' ] . ":" . $row[ 'api_key' ];
                } else {
                    $response[] = $row[ 'proxy_ip' ] . ":" . $row[ 'proxy_port' ];
                }
            }
        }

        return $this->getTextPlainResponse($response);
    }

    // Deprecated

    /**
     * Based on "plist.ip.php" (deprecated since ACL)
     *
     * @return Response
     */
    public function ipAction()
    {
        return $this->getTextPlainStreamResponse(function() {
            $feedBox = FeedBoxFactory::build();
            while ($data = $feedBox->pullPartialRow('ip')) {
                echo "{$data['userIp']} {$data['serverIp']}" . PHP_EOL;
            }
        });
    }

    /**
     * Based on "plist.pw.php" (deprecated since ACL)
     *
     * @return Response
     */
    public function pwdAction()
    {
        return $this->getTextPlainStreamResponse(function() {
            $feedBox = FeedBoxFactory::build();
            while ($data = $feedBox->pullPartialRow('pwd')) {
                echo "{$data['serverIp']}:{$data['login']}:{$data['secret']}" . PHP_EOL;
            }
        });
    }
}
