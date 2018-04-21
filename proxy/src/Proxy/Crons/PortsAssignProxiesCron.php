<?php

namespace Proxy\Crons;

use Common\Events\Events\AbstractEventWithResult;
use Proxy\Assignment\Port\CommonPackageContext;
use Proxy\Assignment\Port\IPv4;
use Proxy\Assignment\Port\IPv4\ProxyPort;
use Proxy\Assignment\Port\PortInterface;
use Proxy\Assignment\RotationAdviser\IPv4\RotationAdviser;
use Proxy\Events\CheckPortsAssignment;

/**
 * Class PortsAssignProxiesCron base on "assignment.php"
 *
 * @package Reseller\Crons
 */
class PortsAssignProxiesCron extends AbstractPortsAssignProxiesCron
{
    protected $settings = [
        'dryRun' => false,
        'skipUnassignedTimeout' => 0
    ];

    /**
     * @var array
     */
    protected $adviser;
    protected $emailQueue = [];

    public function run()
    {
        $specialAdviser = $this->getRotationAdviser()->getSpecialCustomerAdviser();

        foreach ($this->getConn()->fetchAll(
            "SELECT up.*, pr.dead, pr.ip as pip, pr.port as pport, pending_replace, preg.country as region_country, 
              pu.sneaker_location, pu.reseller_id, pup.type as package_type
            FROM user_ports up
            LEFT JOIN proxies_ipv4 pr ON up.proxy_ip = pr.id
            LEFT JOIN proxy_regions preg ON up.region_id = preg.id
            LEFT JOIN proxy_users pu ON up.user_id = pu.id AND up.user_type = :typeClient
            LEFT JOIN proxy_user_packages pup ON pup.country = up.country AND pup.category = up.category AND
              up.user_type = :typeClient AND pup.user_id = up.user_id
            WHERE (proxy_ip = 0 OR up.pending_replace = 1) AND 
              (up.region_id > 0 OR 
              (up.country NOT IN (:countryUS, :countryINTL) OR 
              up.category NOT IN (:categorySemi, :categoryDedi, :categorySneaker)))
              AND (up.time_assignment_attempt <= :attemptsTimeout OR up.time_assignment_attempt IS NULL)
            ORDER BY user_type, user_id, country, category DESC",
            [
                'typeClient' => IPv4\Port::TYPE_CLIENT,
                'countryUS' => IPv4\Port::COUNTRY_US,
                'countryINTL' => IPv4\Port::COUNTRY_INTERNATIONAL,
                'categorySemi' => IPv4\Port::CATEGORY_SEMI_DEDICATED,
                'categoryDedi' => IPv4\Port::toOldCategory(IPv4\Port::CATEGORY_DEDICATED),
                'categorySneaker' => IPv4\Port::CATEGORY_SNEAKER,
                'attemptsTimeout' => date('Y-m-d H:i:s', time() - max($this->getSetting('skipUnassignedTimeout'), 0) * 60)
            ]
        ) as $row) {
            $port = ProxyPort::fromArray($row);
            $port->getContext()->setNeed(!$row['pending_replace'] ?
                CommonPackageContext::NEED_NEW : CommonPackageContext::NEED_REPLACE
            );

            if (!empty($row['reseller_id'])) {
                /** @var AbstractEventWithResult $event */
                $event = $this->getEvents()->emit(new CheckPortsAssignment($port, $row[ 'reseller_id' ]));
                if (!$event->getResult()) {
                    $this->warn('Port assignment is disabled by "CheckPortsAssignment" event result', ['row' => $row],
                        ['userId' => $row['user_id']]);
                    continue;
                }
            }

            if ($specialAdviser->isAbleToHandle($port)) {
                // Must be handled by that handler
            }
            // No sneaker location defined
            elseif (IPv4\Port::CATEGORY_SNEAKER == $port->getCategory() and IPv4\Port::TYPE_CLIENT == $port->getUserType() and
                IPv4\Port::COUNTRY_US == $port->getCountry() and !$port->getSneakerLocation()) {
                continue;
            }

            // No port location defined
            elseif (in_array($port->getCountry(), [IPv4\Port::COUNTRY_US, IPv4\Port::COUNTRY_INTERNATIONAL]) AND
                in_array($port->getCategory(), [IPv4\Port::CATEGORY_DEDICATED, IPv4\Port::CATEGORY_SEMI_DEDICATED]) AND
                !$port->getRegionId()) {
                continue;
            }

            $this->adviseAndAssignNewProxy($port, false);
        }

        return true;
    }

    /**
     * Can be extended. Output info in either cases port found or not
     *
     * @param PortInterface $port
     * @param int|bool $newProxyId False if not found
     * @param int $previousProxyId
     * @return string
     */
    protected function getProxyInfoLog(PortInterface $port, $newProxyId, $previousProxyId)
    {
        return sprintf(
            'For user "%s" proxy "%s-%s"' .
            ($newProxyId ?
                " assigned proxy id $newProxyId (was $previousProxyId)" :
                " proxy not assigned (current is $previousProxyId)") . ', port %s',
            $port->getUserId(), $port->getCountry(), $port->getCategory(), $port->getId()
        );
    }

    protected function buildRotationAdviser()
    {
        return new RotationAdviser($this->getConn(), $this->logger);
    }
}
