<?php

namespace ProxyReseller\Controller\ApiV20;

use Doctrine\DBAL\Connection;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\Assignment\RotationAdviser\IPv4\RotationAdviser;
use ProxyReseller\Controller\AbstractVersionedController;
use ProxyReseller\Controller\ApiV20\Traits\CommonMethodsTrait;

class MiscController extends AbstractVersionedController
{
    use CommonMethodsTrait;

    public function getLocationsAvailabilityAction($userId)
    {
        $user = $this->getUser($userId);
        $excludeLocations = array_map('strtolower',
            (new RotationAdviser($this->getConn(), $this->logger))->getFromConfig('fakeLocations')
        );

        $data = $this->getConn()->executeQuery(
            "SELECT *
            FROM proxy_regions r
            LEFT JOIN (
                    SELECT region_id, count(*) as `semi-3`
                    FROM (
                            SELECT pr.*
                            FROM  proxies_ipv4 pr
                            LEFT JOIN user_ports up on up.proxy_ip = pr.id
                            WHERE pr.active = 1 AND pr.dead = 0 AND pr.static = 1
                            and ( up.category = 'semi-3' or up.category IS NULL )
                            and pr.id NOT IN (SELECT proxy_ip FROM user_ports WHERE user_id = :userId AND user_type = :userType)
                            GROUP BY pr.id
                            HAVING COUNT(up.id) < 4
                            ORDER BY null
                    ) as s
                    GROUP BY region_id
                    ORDER BY null
            ) as semi ON r.id = semi.region_id
            LEFT JOIN (
                    SELECT region_id, count(*) as dedicated
                    FROM proxies_ipv4
                    WHERE id NOT IN (SELECT proxy_ip FROM user_ports)
                    AND static = 1 and active = 1 and dead = 0
                    GROUP BY region_id
                    ORDER BY null
            ) as dedi ON r.id = dedi.region_id
            LEFT JOIN (
                    SELECT region_id, count(*) as rotating
                    FROM proxies_ipv4
                    WHERE id NOT IN (SELECT proxy_ip FROM user_ports)
                    AND static = 0 and active = 1 and dead = 0
                    GROUP BY region_id
                    ORDER BY null
            ) as rotate ON r.id = rotate.region_id
            WHERE r.country IN (:countries)", [
            'countries' => Port::getValidCountries(),
            'userType'  => Port::TYPE_CLIENT,
            'userId'    => $user[ 'id' ],
        ], ['countries' => Connection::PARAM_STR_ARRAY, 'excludeRegions' => Connection::PARAM_STR_ARRAY]
        );

        $availability = [];
        foreach ($data as $region) {
            if (in_array(strtolower($region['region']), $excludeLocations)) {
                continue;
            }

            $availableCategories = [];

            foreach ($region as $field => $value) {
                if (Port::isCategoryCountryAvailable($field, $region[ 'country' ])) {
                    $availableCategories[ $field ] = $value;
                }
            }

            if (!$availableCategories) {
                continue;
            }

            $availability[ $region[ 'country' ] ][$region[ 'id' ]] = [
                'state'      => $region[ 'state' ],
                'city'       => $region[ 'region' ],
                'categories' => []
            ];

            foreach ($availableCategories as $category => $count) {
                $availability[ $region[ 'country' ] ][$region[ 'id' ]][ 'categories' ][ $category ] = (int) $count;
            }
        }

        return [
            'list' => $availability
        ];
    }

    public function getProxyTypesAllowedAction()
    {
        return [
            'list' => Port::getValidCategoriesCountriesAvailable()
        ];
    }
}