<?php

namespace Reseller\Controller;

use Proxy\Assignment\Port\IPv4\Port;
use Proxy\Assignment\Port\IPv4\OldResellerPort;

class ApiProxyInfoV1Controller extends AbstractAPIController
{
    
    public function locationAction()
    {
        $sql = "SELECT r.id as region_id, r.country as country, region as region_name, 
                    semi as ?, dedi as ?, rotate as ?, sneaker as ?, supreme as ?
                FROM proxy_regions r
                LEFT JOIN (
                    SELECT region_id, count(*) as semi
                    FROM (
                        SELECT pr.*
                        FROM  `proxies_ipv4` pr
                        LEFT JOIN user_ports up on up.proxy_ip = pr.id
                        WHERE pr.`active` = 1 
                        AND pr.`dead` != 1 
                        AND pr.`static` = 1 
                        and ( up.type = 'us-semi-3' or up.type IS NULL )
                        GROUP BY pr.id
                        HAVING COUNT(up.id) < 3
                    ) as s
                    GROUP BY region_id
                ) as semi ON r.id = semi.region_id
                LEFT JOIN (
                    SELECT region_id, count(*) as dedi
                    FROM proxies_ipv4
                    WHERE id NOT IN (SELECT proxy_ip FROM user_ports)
                    AND static = 1 and pristine = 1 and active = 1 and dead = 0
                    GROUP BY region_id			
                ) as dedi ON r.id = dedi.region_id
                LEFT JOIN (
                    SELECT region_id, count(*) as rotate
                    FROM proxies_ipv4
                    WHERE id NOT IN (SELECT proxy_ip FROM user_ports)
                    AND static = 0 and active = 1 and dead = 0
                    GROUP BY region_id	
                ) as rotate ON r.id = rotate.region_id
                LEFT JOIN (
                    SELECT If(host_loc = 'los angeles, ca', 13, 2) as region_id, count(*) as sneaker
                    FROM proxies_ipv4
                    WHERE id NOT IN (SELECT proxy_ip FROM user_ports)
                    AND host_loc IN ('los angeles, ca', 'buffalo, ny')
                    AND static = 1 and active = 1 and dead = 0                
                    GROUP BY host_loc
                ) as sneaker ON r.id = sneaker.region_id
                LEFT JOIN (
                    SELECT If(host_loc = 'los angeles, ca', 13, 2) as region_id, count(*) as supreme
                    FROM proxies_ipv4
                    WHERE id NOT IN (SELECT proxy_ip FROM user_ports)
                    AND host_loc IN ('los angeles, ca', 'buffalo, ny')
                    AND static = 1 and active = 1 and dead = 0
                    GROUP BY host_loc
                ) as supreme ON r.id = supreme.region_id
                WHERE country IN ('us','br','de')";
        
        $return = [];
        $locations = $this->getConn('proxy')->fetchAll($sql, [
            Port::CATEGORY_SEMI_DEDICATED,
            Port::CATEGORY_DEDICATED,
            Port::CATEGORY_ROTATING,
            Port::CATEGORY_SNEAKER,
            Port::CATEGORY_SUPREME
        ]);

        foreach($locations as $location) {
            foreach (Port::getValidCategories() as $category) {
                if (in_array($location['region_id'], [1, 32]) or in_array($location['region_name'], ['Mixed'])) {
                        if (OldResellerPort::isCategoryCountryAvailable($category, $location['country'])) {
                            $return[ $location[ 'country' ] ][ $location[ 'region_name' ] ]
                                [ OldResellerPort::toOldCategory($category) ] = true;
                        }
                }
                else {
                    if (OldResellerPort::isCategoryCountryAvailable($category, $location['country'])) {
                        $return[ $location[ 'country' ] ][ $location[ 'region_name' ] ]
                            [ OldResellerPort::toOldCategory($category) ] =
                                ($location[ $category ]) ?
                                    (int) $location[ $category ] : false;
                    }
                }
            }
        }

        return $return;
    }
    
    public function countryAction()
    {
        $return = [];

        foreach (OldResellerPort::getValidCategoriesCountriesAvailable() as $category => $countries) {
            foreach ($countries as $country) {
                $return[$country][] = OldResellerPort::toOldCategory($category);
            }
        }
        return $return;
    }
}