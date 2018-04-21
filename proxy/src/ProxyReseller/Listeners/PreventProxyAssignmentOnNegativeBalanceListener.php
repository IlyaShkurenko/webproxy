<?php

namespace ProxyReseller\Listeners;

use Common\Events\AbstractListener;
use Common\Events\Events\AbstractEvent;
use InvalidArgumentException;
use Proxy\Events\CheckPackageAssignment;
use Proxy\Events\CheckPortsAssignment;

class PreventProxyAssignmentOnNegativeBalanceListener extends AbstractListener
{

    protected $events = [
        CheckPackageAssignment::class,
        CheckPortsAssignment::class
    ];

    protected $cache = [];

    public function handle(AbstractEvent $e)
    {
        if ($e instanceof CheckPortsAssignment) {
            if (!isset($this->cache['reseller'][$e->getResellerId()])) {
                $reseller = $this->getConn()->executeQuery('SELECT * FROM resellers WHERE id = ?', [$e->getResellerId()])->fetch();
                if (!$reseller) {
                    throw new InvalidArgumentException('No resellers is found with id: ' . $e->getResellerId());
                }

                $this->cache['reseller'][$e->getResellerId()] = $reseller['credits'] > 0;
            }

            $e->setResult(!!$this->cache['reseller'][$e->getResellerId()]);
        }
    }
}
