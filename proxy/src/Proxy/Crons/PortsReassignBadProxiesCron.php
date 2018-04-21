<?php

namespace Proxy\Crons;

use Common\Events\Events\AbstractEventWithResult;
use Doctrine\DBAL\Connection;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\Assignment\Port\IPv4\ProxyPort;
use Proxy\Assignment\Port\PortInterface;
use Proxy\Events\CheckPortsAssignment;
use Proxy\Assignment\Port\CommonPackageContext;

/**
 * Class PortsReassignBadProxiesCron based on "statics.php"
 *
 * @package Reseller\Crons
 */
class PortsReassignBadProxiesCron extends PortsAssignProxiesCron
{
    protected $config = [
        'schedule' => '*/15 * * * *'
    ];

    protected $settings = [
        'dryRun' => false,
        'deadStopCount' => 150,
        'emailFrom' => 'admin@blazingseollc.com',
        'customerEmailAliases' => [
            'krateson@outlook.com' => 'coscallelinden1@gmail.com'
        ],
        'skipRotationForUsers' => [
            'period' => [3133],
            'dead' => [3133],
            'inactive' => [3133]
        ],
        'skipUnassignedTimeout' => 0
    ];

    protected $emailQueue = [];
    protected $lastEmailSent = false;
    protected $lastResellerId;

    public function run()
    {
        $count = $this->getConn()->executeQuery("
          SELECT count(*) as count FROM proxies_ipv4 WHERE dead = 1 and active = 1 and country = ?
        ", [Port::COUNTRY_US])->fetchColumn();

        if ($this->getSetting('deadStopCount') and $this->getSetting('deadStopCount') <= $count) {
            $this->alertEmail(
                "Did not run rotations due to dead count : $count",
                "Did not run rotations due to dead count : $count for country US"
            );

            $this->output("Did not run rotations due to dead count : $count");

            return false;
        }

        foreach ($this->getConn()->fetchAll("
            SELECT up.*, pr.dead, pr.active, pr.ip as pip, pr.port as pport, au.sneaker_location, up.proxy_ip as proxy_id,
            pu.reseller_id
            FROM user_ports up
            JOIN all_users au ON au.user_type = up.user_type and au.user_id = up.user_id
            LEFT JOIN proxies_ipv4 pr ON up.proxy_ip = pr.id
            LEFT JOIN proxy_users pu ON up.user_id = pu.id AND up.user_type = :typeClient
            WHERE proxy_ip != 0 AND (
                (((DATE_ADD(last_rotated, INTERVAL rotation_time MINUTE) < :now and au.rotate_30 and proxy_ip) AND
                  category IN(:categoriesRotated)) AND up.user_id NOT IN (:skipUsersPeriod))
                OR ((dead = 1 and au.rotate_ever = 0) AND up.user_id NOT IN (:skipUsersDead))
                OR (((pr.active = 0 AND pr.new = 0) OR pr.id IS NULL) AND up.user_id NOT IN (:skipUsersInactive))
            )
            AND category NOT IN(:categories)
            AND (up.time_assignment_attempt <= :attemptsTimeout OR up.time_assignment_attempt IS NULL)
            ORDER BY country, category, user_id"
            , [
                'now'               => date('Y-m-d H:i:s'),
                'categories'        => [Port::toOldCategory(Port::CATEGORY_ROTATING)],
                'categoriesRotated' => [
                    Port::CATEGORY_SEMI_DEDICATED,
                    Port::CATEGORY_SNEAKER,
                    Port::toOldCategory(Port::CATEGORY_DEDICATED)
                ],
                'skipUsersPeriod'   => ($value = $this->getSetting('skipRotationForUsers.period')) ? $value : [''],
                'skipUsersDead'     => ($value = $this->getSetting('skipRotationForUsers.dead')) ? $value : [''],
                'skipUsersInactive' => ($value = $this->getSetting('skipRotationForUsers.inactive')) ? $value : [''],
                'typeClient' => Port::TYPE_CLIENT,
                'attemptsTimeout' => date('Y-m-d H:i:s', time() - max($this->getSetting('skipUnassignedTimeout'), 0) * 60)
            ], [
                'categories'        => Connection::PARAM_STR_ARRAY,
                'categoriesRotated' => Connection::PARAM_STR_ARRAY,
                'skipUsersPeriod'   => Connection::PARAM_STR_ARRAY,
                'skipUsersDead'     => Connection::PARAM_STR_ARRAY,
                'skipUsersInactive' => Connection::PARAM_STR_ARRAY,
            ]) as $row) {
            if (!empty($row['reseller_id'])) {
                /** @var AbstractEventWithResult $event */
                $port = ProxyPort::fromArray($row);
                $event = $this->getEvents()->emit(new CheckPortsAssignment($port, $row['reseller_id']));
                if (!$event->getResult()) {
                    $this->warn('Port assignment is disabled by "CheckPortsAssignment" event result', ['row' => $row],
                        ['userId' => $row['user_id']]);
                    continue;
                }
            }

            $this->lastResellerId = $row['reseller_id'];
            $this->adviseAndAssignNewProxy(ProxyPort::fromArray($row), !($row['dead'] or !$row['active']));
        }

        $this->pushEmailQueue();

        return true;
    }

    protected function assignNewProxy(PortInterface $port, $newProxyId, $updateRotated = true)
    {
        $oldProxyId = $port->getProxyId();

        parent::assignNewProxy($port, $newProxyId, $updateRotated);
        $this->lastEmailSent = false;

        // Email notifications
        if ($port instanceof ProxyPort and $oldProxyId) {
            if (Port::TYPE_CLIENT == $port->getUserType() and
                !in_array($port->getCategory(), [Port::CATEGORY_SNEAKER]) and
                $port->getProxyId()
            ) {
                $shouldTrulySend = (1 == $this->lastResellerId);

                if (!$port->isActive()) {
                    if ($shouldTrulySend) {
                        $this->queueEmailNonActive($port->getUserId(), $oldProxyId, $newProxyId);
                    }
                    $this->lastEmailSent = true;
                } elseif ($port->isDead()) {
                    if ($shouldTrulySend) {
                        $this->queueEmailDead($port->getUserId(), $oldProxyId, $newProxyId);
                    }
                    $this->lastEmailSent = true;
                } else {
                    if ($shouldTrulySend) {
                        $this->queueEmailRotating($port->getUserId(), $oldProxyId, $newProxyId);
                    }
                    $this->lastEmailSent = true;
                }
            }
        }
    }

    protected function notAssignedNewProxy(PortInterface $port)
    {
        $this->lastEmailSent = false;
    }

    protected function getProxyInfoLog(PortInterface $port, $newProxyId, $previousProxyId)
    {
        $output = parent::getProxyInfoLog($port, $newProxyId, $previousProxyId);

        if ($port instanceof ProxyPort) {
            if (!$port->isActive()) {
                $output .= ', reason - not active';
            } elseif ($port->isDead()) {
                $output .= ', reason - dead';
            } else {
                $output .= ', reason - rotation';
            }
        }

        if ($this->lastEmailSent) {
            if (1 == $this->lastResellerId) {
                $output .= ', email sent';
            }
            else {
                $output .= ', email deactivated (resellers rule)';
            }
        }

        return $output;
    }

    // --- Email notifications stuff

    protected function queueEmailNonActive($userId, $fromProxy, $toProxy)
    {
        $this->emailQueue[ 'active' ][ $userId ][] = ['from' => $fromProxy, 'to' => $toProxy];

        return $this;
    }

    protected function queueEmailDead($userId, $fromProxy, $toProxy)
    {
        $this->emailQueue[ 'dead' ][ $userId ][] = ['from' => $fromProxy, 'to' => $toProxy];

        return $this;
    }

    protected function queueEmailRotating($userId, $fromProxy, $toProxy)
    {
        $this->emailQueue[ 'rotating' ][ $userId ][] = ['from' => $fromProxy, 'to' => $toProxy];

        return $this;
    }

    protected function pushEmailQueue()
    {
        foreach ($this->emailQueue as $type => $data) {
            foreach ($data as $userId => $proxies) {
                switch ($type) {
                    case 'active':
                        $subject = "Blazing Proxy Issue";
                        $message = "We have had to deactive the proxy you were using at our datacenter. " .
                            "The proxies replaced are:" .
                            PHP_EOL . PHP_EOL;
                        break;

                    case 'dead':
                        $subject = "Blazing Proxy Dead Proxies";
                        $message = "We have determined a proxy of yours to be down. " .
                            "It could be software or hardware related on our proxy server, " .
                            "but nonetheless we have replaced your proxy for you automatically. " .
                            "Most times proxies are down for a matter of a few minutes, and at most a few hours. " .
                            "If you do not want to bother replacing any dead proxies, " .
                            "make sure to CHECK the top box on your settings page: " .
                            "http://www.blazingseollc.com/proxy/dashboard/?page=settings" . PHP_EOL . PHP_EOL .
                            "The proxies replaced are:" . PHP_EOL . PHP_EOL;
                        break;

                    case 'rotating':
                        $subject = "Blazing Proxy Rotation";
                        $message = "Your proxies have changed due to the 30-day rotation period.
Some or all of your proxies have changed, your new ones are below and in your proxy dashboard (http://blazingseollc.com/proxy/dashboard)." . PHP_EOL .
                        "The proxies replaced are:" . PHP_EOL . PHP_EOL;
                        break;

                    default:
                        continue 2;
                }

                $userInfo = $this->getUserInfo($userId);
                $proxiesList = [
                    'from' => [],
                    'to' => []
                ];

                foreach ($proxies as $proxySet) {
                    foreach ($proxySet as $kind => $id) {
                        $proxyInfo = $this->getProxyInfo($id);

                        if (!$proxyInfo) {
                            $proxiesList[$kind][] = '[unknown]';
                            continue;
                        }

                        $ip = $proxyInfo['ip'];
                        $port = 'PW' == $userInfo['preferred_format'] ? $this->getSetting('pwPort') : $proxyInfo['port'];

                        $proxiesList[$kind][] = "$ip:$port";
                    }
                }

                $message .= join($proxiesList['from'], PHP_EOL) . PHP_EOL . PHP_EOL;
                $message .= 'with the following proxies:' . PHP_EOL . PHP_EOL;
                $message .= join($proxiesList['to'], PHP_EOL) . PHP_EOL . PHP_EOL;

                $message .= '---' . PHP_EOL . PHP_EOL;
                $message .= 'Your full list of proxies is below:' . PHP_EOL . PHP_EOL;

                foreach($this->getUserProxies($userId) as $proxy) {

                    if (Port::toOldCategory(Port::CATEGORY_ROTATING) == $proxy['category']) {
                        $ip = $proxy['server_ip'];
                        $port = $proxy['server_port'];
                    }
                    else {
                        $ip = $proxy['ip'];
                        $port = 'PW' == $userInfo['preferred_format'] ? $this->getSetting('pwPort') : $proxy['port'];
                    }

                    $message .= "$ip:$port" . PHP_EOL;

                    // $category = $port->category;

                    // if ($category == 'rotate' || $category == 'google') {
                    //    $message .= $port->server_ip . ":" . $port->port . PHP_EOL;
                }

                $email = $userInfo['email'];

                // Customers email aliases
                $aliases = $this->getSetting('customerEmailAliases');
                if (!empty($aliases[$email])) {
                    $email = $aliases[$email];
                }

                mail($email, $subject, $message, "From: " . $this->getSetting('emailFrom'));
                $this->debug("Mail to \"$email\" with subject \"$subject\" have been sent", [
                    'message' => $message,
                    'from'    => $this->getSetting('emailFrom'),
                    'subject' => $subject,
                    'to'      => $email
                ], ['userId' => $userId]);
            }
        }
        $this->emailQueue = [];

        return $this;
    }

    protected function getUserInfo($userId)
    {
        return $this->getConn()->executeQuery("SELECT * FROM `proxy_users` WHERE `id` = ?", [$userId])->fetch();
    }

    protected function getProxyInfo($proxyId)
    {
        return $this->getConn()->executeQuery("SELECT * FROM `proxies_ipv4` WHERE `id` = ?", [$proxyId])->fetch();
    }

    protected function getUserProxies($userId)
    {
        return $this->getConn()->executeQuery("
            SELECT up.*, p.ip as ip, ps.server_ip, p.port as port, up.port as server_port
            FROM `user_ports` up
            LEFT JOIN proxy_server ps ON ps.id = up.server_id
            INNER JOIN proxies_ipv4 p ON up.proxy_ip = p.id
            WHERE `user_id` = ? ORDER BY `up`.`port` ASC
            ", [$userId])->fetchAll();
    }
}
