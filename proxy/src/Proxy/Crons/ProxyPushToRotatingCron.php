<?php

namespace Proxy\Crons;

use Axelarge\ArrayTools\Arr;
use Doctrine\DBAL\Connection;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\Assignment\RotationAdviser\IPv4\RotationAdviser;

/**
 * Class ProxyPushToRotatingCron
 *
 * @package Proxy\Crons based on "notication.php"
 */
class ProxyPushToRotatingCron extends AbstractDefaultSettingsCron
{
    protected $config = [
        'schedule' => '* * * * *',
        'enabled' => true
    ];

    protected $settings = [
        'dryRun'        => false,
        'proxiesBuffer' => 5
    ];

    public function run()
    {
        $countries = [Port::COUNTRY_US, Port::COUNTRY_GERMANY, Port::COUNTRY_BRAZIL];
        $perIp = (new RotationAdviser($this->getConn(), $this->logger))->getFromConfig('rules.rotating.perIp');

        foreach ($countries as $country) {
            $data = $this->getConn()->executeQuery(
                "SELECT proxies, ports 
                FROM (SELECT COUNT(*) as proxies FROM proxies_ipv4 
                  WHERE country = :country AND `active` = 1 AND `dead`= 0 and static = 0) as t1
                JOIN (SELECT count(*) as ports FROM user_ports WHERE country = :country AND category = :category) as t2",
                ['country' => $country, 'category' => Port::toOldCategory(Port::CATEGORY_ROTATING)])->fetch();

            $data['perIp'] = $perIp;
            $data['proxiesAvailable'] = $data['proxies'] * $perIp;
            $data['proxiesBuffer'] = ceil($this->getSetting('proxiesBuffer') / $perIp);

            // Not enough proxies
            if ($data['proxiesAvailable'] < ($data['ports'] + $data['proxiesBuffer'])) {
                $needed = ($data['ports'] + $data['proxiesBuffer']) - $data['proxiesAvailable'];
                $whereSql = 'active = 1 AND dead = 0 AND static = 1 AND country = :country
                      AND id NOT IN (SELECT proxy_ip FROM user_ports) 
                      AND id NOT IN (SELECT proxy_id FROM user_ports_frozen) 
                      ORDER BY times_assigned DESC, rand()';
                $available = $this->getConn()->executeQuery(
                    "SELECT COUNT(*) FROM proxies_ipv4 WHERE $whereSql", ['country' => $country])->fetchColumn();

                // Not enough at all
                if (!$available or $available < $data['proxiesBuffer']) {
                    // + send email later
                    $this->log("No \"$country\" static proxies available to pull, {$data['proxiesBuffer']} needed, but $available available", $data);
                    continue;
                }
                // Almost enough
                elseif ($needed > $available) {
                    $ids = $this->getConn()
                        ->executeQuery("SELECT id FROM proxies_ipv4 WHERE $whereSql LIMIT $available",
                            ['country' => $country])->fetchAll();
                    $ids = Arr::pluck($ids, 'id');

                    $this->log("Not enough \"$country\" static proxies, $needed needed, but $available available. " .
                        'Only ' . ($needed - $available) . ' have been assigned',
                        array_merge($data, [ 'pushedIds' => $ids ]));

                    if (!$this->getSetting('dryRun')) {
                        $this->getConn()->executeUpdate("UPDATE proxies_ipv4 SET static = 0 WHERE id IN (:ids)",
                            ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);
                    }
                }
                // Enough
                else {
                    $ids = $this->getConn()
                        ->executeQuery("SELECT id FROM proxies_ipv4 WHERE $whereSql LIMIT $needed",
                            ['country' => $country])->fetchAll();
                    $ids = Arr::pluck($ids, 'id');

                    $this->log("Pushed $needed \"$country\" proxies from $available available",
                        array_merge($data, [ 'pushedIds' => $ids ]));
                    if (!$this->getSetting('dryRun')) {
                        $this->getConn()->executeUpdate("UPDATE proxies_ipv4 SET static = 0 WHERE id IN (:ids)",
                            ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);
                    }
                }
            }
        }

        return true;
    }
}
