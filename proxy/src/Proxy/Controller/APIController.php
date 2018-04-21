<?php

namespace Proxy\Controller;

use Application\AbstractApiController;
use Axelarge\ArrayTools\Arr;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\Assignment\PortAssigner;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class APIController extends AbstractApiController
{
    protected $convertResponse = 'json';

    protected $logPath = 'api/proxy.log';

    public function __construct(Application $app)
    {
        parent::__construct($app);
        if ($this->logger) {
            $this->logger->setAppName(str_replace('/all', '/api', $this->logger->getAppName()));
        }
    }

    public function forcePortsSync(Request $request)
    {
        $userId = $request->get('userId');
        $userType = $request->get('userType');
        $country = $request->get('country');
        $category = $request->get('category');

        if ($userId) {
            $this->logger->addSharedIndex('userId', $userId);
        }

        $this->assertOrException($userId and $userType and $country and $category, 'Wrong request ' . json_encode($this->getRequestParameters()));
        $category = Port::toOldCategory($category);

        $packageId = $this->getConn()->executeQuery(
            'SELECT id
            FROM proxy_user_packages pup            
            WHERE user_id = :userId AND pup.country = :country AND pup.category = :category AND pup.ip_v = :ipv4', [
            'userId'   => $userId,
            'country'  => $country,
            'category' => Port::toOldCategory($category),
            'ipv4'     => Port::INTERNET_PROTOCOL
        ])->fetchColumn();

        $this->assertFalseOrException(!$packageId, 'No package exists: ' . json_encode([
                'user id'   => $userId,
                'country'   => $country,
                'category'  => $category,
                'user type' => $userType
            ]
        ));

        $assigner = new PortAssigner($this->getConn(), $this->logger);
        $result = $assigner->syncPackage($packageId);

        if ($result->isIncremented()) {
            return [
                'status' => 'Added ' . $result->getChangedCount() . ' ports',
                'data' => [
                    'user id'   => $userId,
                    'country'   => $country,
                    'category'  => $category,
                    'user type' => $userType
                ]
            ];
        }
        elseif ($result->isDecremented()) {
            return [
                'status' => 'Removed ' . $result->getChangedCount() . ' ports',
                'data' => [
                    'user id'   => $userId,
                    'country'   => $country,
                    'category'  => $category,
                    'user type' => $userType,
                    'removed'   => array_map(function($row) {
                        if ($row instanceof Port) {
                            return ['proxy_ip' => $row->getProxyId(), 'region_id' => $row->getRegionId()];
                        }
                        else {
                            return Arr::only($row, ['proxy_ip', 'region_id', 'rotation_time']);
                        }
                    }, $result->getRemovedPorts())

                ]
            ];
        }
        else {
            return [
                'status' => 'No affected ports'
            ];
        }
    }
}
