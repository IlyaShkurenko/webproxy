<?php

namespace Blazing\Vpn\Client\Events\Client;

use Blazing\Vpn\Client\Container;
use Blazing\Vpn\Client\Events\AbstractFunction;

class ClientDashboard extends AbstractFunction
{
    protected $name = 'ClientArea';

    /**
     * @param array
     * @return mixed
     */
    protected function execute(array $args = null)
    {
        if (empty($args[ 'serviceid' ])) {
            return [];
        }
        //die($this->getRelativeTemplatePath('client.dashboard'));

        $userId = $args[ 'userid' ];
        $serviceId = $args[ 'serviceid' ];
        $locations = Container::getInstance()->getVpnApi()->getServiceLocations($userId, $serviceId, false)['list'];

        // Prepare urls
        foreach ($locations as $i => $location) {
            $locations[$i]['url'] = 'clientarea.php?' . http_build_query(array_merge($_GET, [
                'modop' => 'custom',
                'a'     => DownloadKey::NAME,
                'uid'   => $location[ 'uid' ]
            ]));
        }

        return $this->view('client.dashboard.tpl', ['locations' => $locations]);
    }
}
