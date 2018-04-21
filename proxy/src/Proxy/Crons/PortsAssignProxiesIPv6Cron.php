<?php

namespace Proxy\Crons;

use Common\Events\Events\AbstractEventWithResult;
use Proxy\Assignment\Port\IPv6\Port;
use Proxy\Assignment\Port\PortInterface;
use Proxy\Assignment\RotationAdviser\IPv4;
use Proxy\Assignment\RotationAdviser\IPv6;
use Proxy\Events\CheckPortsAssignment;

class PortsAssignProxiesIPv6Cron extends AbstractPortsAssignProxiesCron
{
    protected $settings = [
        'dryRun' => false
    ];

    public function run()
    {
        $stmt = $this->getConn()->executeQuery("
            SELECT p.*, up.*, p.id as package_id, u.reseller_id
            FROM user_ports_ipv6 up
            INNER JOIN proxy_user_packages p ON p.id = up.package_id
            INNER JOIN proxy_users u ON u.id = p.user_id
            WHERE up.block_id IS NULL
            ORDER BY p.user_id ASC, p.id ASC, up.id
        ");
        while ($row = $stmt->fetch()) {
            $port = Port::fromArray($row);

            if (!empty($row['reseller_id'])) {
                /** @var AbstractEventWithResult $event */
                $event = $this->getEvents()->emit(new CheckPortsAssignment($port, $row[ 'reseller_id' ]));
                if (!$event->getResult()) {
                    $this->warn('Port assignment is disabled by "CheckPortsAssignment" event result', ['row' => $row],
                        ['userId' => $row['user_id']]);
                    continue;
                }
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
        $previousProxyId = $previousProxyId ? $previousProxyId : 'null';

        return sprintf(
            'For user "%s" proxy "%s %s"' .
            ($newProxyId ?
                " assigned block id $newProxyId (was $previousProxyId)" :
                " proxy not assigned (current is $previousProxyId)") . ', port %s',
            $port->getUserId(), $port->getType(), $port->getExt(), $port->getId()
        );
    }

    /**
     * @return IPv4\RotationAdviser|IPv6\RotationAdviser
     */
    protected function buildRotationAdviser()
    {
        return new IPv6\RotationAdviser($this->getConn(), $this->logger);
    }
}
