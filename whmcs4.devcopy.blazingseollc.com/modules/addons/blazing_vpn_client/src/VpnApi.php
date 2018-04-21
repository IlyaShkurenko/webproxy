<?php

namespace Blazing\Vpn\Client;

use Blazing\Vpn\Client\Vendor\ApiRequestHandler\AbstractApi;

class VpnApi extends AbstractApi
{
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELLED = 'cancelled';


    public function getServiceData($serviceId)
    {
        return $this->request()->get('/list/package', ['serviceId' => $serviceId]);
    }

    public function getServiceLocations($userId, $serviceId, $addConfigs = true, $uid = null)
    {
        return $this->request()->get('/list', [
            'userId'     => $userId,
            'serviceId'  => $serviceId,
            'addConfigs' => $addConfigs,
            'uid'        => $uid
        ]);
    }

    public function createVpnService($userId, $serviceId)
    {
        return $this->request()->post('/list', ['userId' => $userId, 'serviceId' => $serviceId]);
    }

    public function updateVpnService($userId, $serviceId, $status)
    {
        return $this->request()->put('/list', ['userId' => $userId, 'serviceId' => $serviceId, 'status' => $status]);
    }
}