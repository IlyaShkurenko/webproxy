<?php

namespace Blazing\Vpn\Client\Events\Client;

use Blazing\Vpn\Client\Container;
use Blazing\Vpn\Client\Events\AbstractFunction;
use Blazing\Vpn\Client\Vendor\Symfony\Component\HttpFoundation\Response;
use ErrorException;

class DownloadKey extends AbstractFunction
{
    const NAME = 'DownloadKey';

    protected $name = self::NAME;

    /**
     * @param array
     * @return mixed
     */
    protected function execute(array $args = null)
    {
        $userId = $args[ 'userid' ];
        $serviceId = $args[ 'serviceid' ];
        $uid = $_GET['uid'];

        try {
            $response = Container::getInstance()->getVpnApi()->getServiceLocations($userId, $serviceId, true, $uid);
            if (empty($response['item'])) {
                throw new ErrorException('No location is found');
            }
            $item = $response['item'];

            Response::create($item['config'], 200, [
                'content-type' => 'application/x-openvpn-profile',
                'content-disposition' => sprintf('attachment; filename="%s.ovpn"', join('-', [$item['country'], $item['region'], $item['city']]))
            ])->send();
            die();
        }
        catch (\Exception $e) {
            die($e->getMessage());
        }
    }
}
