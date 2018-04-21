<?php

namespace Reseller\Controller;

use Proxy\Assignment\Port\IPv4\CountedPort;
use Proxy\Assignment\Port\IPv4\OldResellerPort;
use Proxy\Assignment\Port\IPv4\Port;
use Proxy\Assignment\PortAssigner;
use Proxy\Assignment\RotationAdviser\IPv4\RotationAdviser;
use Symfony\Component\HttpFoundation\Request;

class ApiProxyUserV1Controller extends AbstractAPIController
{
    
    public function listAction()
    {
        $resellerUsers = $this->getConn('proxy')->fetchAll('SELECT id as user_id, username
            FROM reseller_users 
            WHERE reseller_id = ?', [
            $this->getReseller()['id']
        ]);

        $this->userLog(null, $resellerUsers);

        return $resellerUsers;
    }
    
    public function getAction($id)
    {
        $reseller = $this->getReseller();
    
        $resellerUserReturns = $this->getConn('proxy')->fetchAll("
            SELECT ru.id as user_id, username, rotate_30, rotate_ever, rotation_type, api_key, auth_type,
                rup.country, rup.category, rup.count, rup.count - ifnull(rup.replacements, 0) as replacements_left,
                rup.expiration, pr.ip, up.port as proxy_port, pr.port as server_port,
                preg.region as proxy_region, ureg.region as port_region, ps.server_ip as rotate_ip
                FROM reseller_users ru
                LEFT JOIN reseller_user_packages rup ON ru.id = rup.reseller_user_id and rup.count > 0
                LEFT JOIN user_ports up ON ru.id = up.user_id and up.user_type = 'RS' and rup.category = up.category and rup.country = up.country
                LEFT JOIN proxy_server ps ON ps.id = up.server_id
                LEFT JOIN proxies_ipv4 pr ON pr.id = up.proxy_ip
                LEFT JOIN proxy_regions preg ON preg.id = pr.region_id
                LEFT JOIN proxy_regions ureg ON ureg.id = up.region_id
                WHERE ru.reseller_id = ? and ru.id = ?", [
            $reseller['id'], $id
        ]);

        $this->assertOrException($resellerUserReturns, 'There is no proxy user with that id', [$id]);
        
        $return = [];
        $plans = [];
        foreach ($resellerUserReturns as $resellerUserReturn) {
            $return['user_id'] = $resellerUserReturn['user_id'];
            $return['username'] = $resellerUserReturn['username'];
            $return['rotate_30'] = (bool)$resellerUserReturn['rotate_30'];
            $return['rotate_ever'] = (bool)$resellerUserReturn['rotate_ever'];
            $return['rotation_type'] = $resellerUserReturn['rotation_type'];            
            $return['auth_type'] = $resellerUserReturn['auth_type'];
            $return['password'] = $resellerUserReturn['api_key'];
            
            $plans[ $resellerUserReturn['country'] ][ $resellerUserReturn['category']]['count'] = $resellerUserReturn['count'];
            $plans[ $resellerUserReturn['country'] ][ $resellerUserReturn['category']]['replacements_left'] = (intval($resellerUserReturn['replacements_left']) > 0) ? intval($resellerUserReturn['replacements_left']) : 0;
            if ($resellerUserReturn['expiration']) {
                $plans[ $resellerUserReturn['country'] ][ $resellerUserReturn['category']]['expiration'] = strtotime($resellerUserReturn['expiration']);
            } else {
                $plans[ $resellerUserReturn['country'] ][ $resellerUserReturn['category']]['expiration'] = null;
            }

            $plans[$resellerUserReturn['country']][$resellerUserReturn['category']]['proxies'][] = array_merge([
                'ip'             => $resellerUserReturn[ 'ip' ],
                'port'           => $resellerUserReturn[ 'server_port' ],
                'proxy_location' => $resellerUserReturn[ 'proxy_region' ],
                'port_location'  => $resellerUserReturn[ 'port_region' ],
            ], 'pw' == $resellerUserReturn['auth_type'] ? [
                'port' => 4444
            ] : [],
                Port::toNewCategory($resellerUserReturn[ 'category' ]) == Port::CATEGORY_ROTATING ? [
                'ip'        => $resellerUserReturn[ 'rotate_ip' ],
                'port'      => $resellerUserReturn[ 'proxy_port' ],
                'actual_ip' => $resellerUserReturn[ 'id' ]
            ] : []);
        }
        
        $rplans = [];
        foreach ($plans as $country => $categories) {
            foreach ($categories as $category => $planDetails) {
                $planDetails['country'] = $country;
                $planDetails['category'] = Port::toNewCategory($category);
                $rplans[] = $planDetails;
            }
        }
        
        $resellerUserIps = $this->getConn('proxy')->fetchAll("
            SELECT ip FROM user_ips WHERE user_type = 'RS' and user_id = ?
        ", [$id]);
                
        $return['plans'] = $rplans;
        $ips = array_map(function($v) { return $v['ip']; }, $resellerUserIps);
         
        if ($ips) {
			$return['ips'] = $ips;
		} else {
			$return['ips'] = null;
		}

        $this->userLog(null, $return);

        return $return;
    }
    
    public function addAction(Request $request)
    {
        $username = $request->get('username');
        $shouldBeUnique = !$request->get('allowNonUnique');

        $reseller = $this->getReseller();
        $this->assertOrException($username, 'Username is Missing', null, 'Add Request missing username');

        if ($shouldBeUnique) {
            if ($this->getConn('proxy')->fetchColumn('SELECT 1 FROM reseller_users WHERE username = ?', [$username])) {
                $this->userLog('Username Already Exists', $username);

                return $this->app->json(['error' => true, 'message' => 'Username Already Exists']);
            }
        }
        $this->getConn('proxy')->insert('reseller_users', [
            'reseller_id' => $reseller['id'],
            'username' => $username,
            'created' => date('Y-m-d H:i:s'),
        ]);
        $lastId = $this->getConn('proxy')->lastInsertId();

        $this->userLog('Inserted New User', [$username, $lastId]);

        return ['user_id' => $lastId];
    }

    public function removeAction($id)
    {
        // Validate input
        $userData = $this->getResellerUser($id);

        $portsCount = $this->getConn('proxy')->delete('user_ports', [
            'user_id' => $id,
            'user_type' => Port::TYPE_RESELLER
        ]);
        $packagesCount = $this->getConn('proxy')->delete('reseller_user_packages', [
            'reseller_user_id' => $id
        ]);
        $this->getConn('proxy')->delete('user_ips', [
            'user_type' => Port::TYPE_RESELLER,
            'user_id' => $id
        ]);
        $this->getConn('proxy')->delete('reseller_users', ['id' => $id]);

        $this->userLog('Removed User', [
            'user'            => $userData,
            'proxies_removed' => $portsCount,
            'plans_removed'   => $packagesCount
        ]);

        return [
            'success'         => true,
            'proxies_removed' => $portsCount,
            'plans_removed'   => $packagesCount
        ];
    }
    
    public function updateAction(Request $request, $id)
    {
        $rotate30 = $request->get('rotate_30');
        $rotateType = $request->get('rotation_type');
        $rotateEver = $request->get('rotate_ever');
        
        $authType = $request->get('auth_type');
        $password = $request->get('password');

        // Validate input
        $this->getResellerUser($id);

        if ($rotate30 === null && $rotateType === null && $rotateEver && $authType === null && $password === null) {
            $this->throwException('Pass at least one setting to update', $id, 'No Setting Passed');
        }
        
        $update = [];
        if ($rotateType !== null) {
            $validRotationType = ['HTTP', 'SOCKS'];
            if (!in_array($rotateType, $validRotationType)) {
                $this->throwException(
                    'Rotation Type is an invalid value. Valid values are: ' . join(', ', $validRotationType),
                    [$id, $rotateType],
                    'Invalid Rotate Value'
                );
            }
            $update['rotation_type'] = $rotateType;
        }
        
        if ($rotate30 !== null) {
            $update['rotate_30'] = $rotate30;
        }
        
        if ($rotateEver !== null) {
            $update['rotate_ever'] = $rotateEver;
        }
        
        if ($authType !== null) {
        	$authType = strtolower($authType);
            if ($authType != 'pw' && $authType != 'ip') {
                $this->throwException(
                    'Auth Type is an invalid value. Valid values are ip and pw',
                    [$id, $authType],
                    'Invalid Auth Type Value'
                );
            }
            $update['auth_type'] = $authType;
        }
        
        if ($password !== null) {
            if (strlen($password) < 6) {
                $this->throwException('Passwords should be at least 6 characters', [$id, $password], 'Invalid Password Value');
            }
            $update['api_key'] = $password;
        }
        
        $this->getConn('proxy')->update('reseller_users', 
            $update, 
            ['id' => $id]);       
            
        $this->userLog(null, [$id, $update]);

        return $this->getAction($id);
    }
    
    public function replaceAction(Request $request, $id)
    {
        $ip = $request->get('ip');
        $reseller = $this->getReseller();

        // Validate input
        $this->getResellerUser($id);
        $this->assertOrException(filter_var($ip, FILTER_VALIDATE_IP), 'Invalid IP Format', [$id, $ip], 'Invalid IP');

        $proxy = $this->getConn('proxy')->fetchAssoc("
            SELECT rup.country, rup.category, rup.count - ifnull(rup.replacements, 0) as replacements_left, up.id as port_id, up.region_id
            FROM reseller_users ru
            JOIN reseller_user_packages rup ON ru.id = rup.reseller_user_id
            JOIN user_ports up ON ru.id = up.user_id and up.user_type = 'RS' and rup.category = up.category and rup.country = up.country
            JOIN proxies_ipv4 pr ON pr.id = up.proxy_ip
            WHERE ru.reseller_id = ? and ru.id = ? and ip = ?", [ $reseller['id'], $id, $ip ]);

        $this->assertOrException($proxy, 'Proxy IP Is Not Associated with this User', [$id, $ip], 'Not Associated');
        $this->assertFalseOrException($proxy['replacements_left'] <= 0, 'No more replacements are left', [$id, $ip], 'No Replacements');

        $port = Port::construct()
            ->setUserId($id)
            ->setUserType(Port::TYPE_RESELLER)
            ->setCountry($proxy['country'])
            ->setRegionId($proxy['region_id'])
            ->setCategory($proxy['category'])
            ->setId($proxy['port_id']);

        if (in_array($port->getCategory(), [
            Port::CATEGORY_SEMI_DEDICATED,
            Port::CATEGORY_DEDICATED,
            Port::CATEGORY_SNEAKER,
            Port::CATEGORY_SUPREME
        ])) {
            $newProxyId = $port->setRotationAdviser(new RotationAdviser($this->getConn('proxy')))
                ->adviseNewProxyId();

            $this->assertOrException($newProxyId, 'No Proxies Availble for Replacement',
                [$id, $ip, $port->getCategory(), $port->getId(), $port->getCountry(), $port->getRegionId()],
                'No Proxies Availble'
            );

            (new PortAssigner($this->getConn('proxy')))->assignPortProxy($port, $newProxyId);

            $this->getConn('proxy')->executeUpdate("UPDATE reseller_user_packages
                SET replacements = replacements + 1
                WHERE reseller_user_id = ? AND country = ? AND category = ?",
                [$port->getUserId(), $port->getCountry(), Port::toOldCategory($port->getCategory())]
            );

            $proxyIp = $this->getConn('proxy')->fetchAssoc('SELECT ip FROM proxies_ipv4 WHERE id = ?', [$newProxyId]);
            $this->userLog(null, [$id, $ip, 'semi-3', $proxy['port_id'], $proxy['country'], $proxy['region_id'], $proxyIp['ip']]);

            return ['ip' => $proxyIp['ip']];
        }

        $this->throwException('You did not pass valid info', [$id, $ip], 'Invalid Info');
    }
    
    public function locationAction(Request $request, $id, $country, $category)
    {
        $category = Port::toOldCategory($category);

        // Validate input
        $this->getResellerUser($id);
        
        $regions = [];
        $locationSubmitted = $request->get('locations');
        foreach($locationSubmitted as $region => $count) {
            $regionValue = $this->getConn('proxy')->fetchAssoc(
                "SELECT * FROM proxy_regions WHERE country = ? and region = ?", 
                [$country, $region]
            );
            if (!$regionValue) {
                throw new \Exception("The region $region is invalid");
            }
            $regions[$regionValue['id']] = $count;
        }
        $totalSubmitted = array_sum($locationSubmitted);        
        
        $locations = $this->getConn('proxy')->fetchAll("SELECT region_id, count(*) as count
            FROM user_ports 
            WHERE user_type = ? and user_id = ? and country = ? and category = ?
            GROUP BY region_id", [Port::TYPE_RESELLER, $id, $country, $category]);
        
        $totalPorts = 0;
        $ports = [];
        foreach($locations as $location) {
            $ports[$location['region_id']] = $location['count'];
            $totalPorts += $location['count'];
        }

        $this->assertFalseOrException($totalSubmitted != $totalPorts, "You need to pass locations for $totalPorts ports",
            ['id' => $id, 'country' => $country, 'category' => $category, 'submitted' => $locationSubmitted, 'totalPorts' => $totalPorts],
            'Total Ports'
        );

        $this->getConn('proxy')->executeUpdate(
            "UPDATE user_ports 
            SET region_id = 0 
            WHERE user_type = ?
                AND user_id = ?
                AND country = ?
                AND category = ?",
            [Port::TYPE_RESELLER, $id, $country, $category]);
        
        foreach ($regions as $region_id => $amount) {        
            $this->getConn('proxy')->executeUpdate(
                "UPDATE user_ports 
                SET region_id = ? 
                WHERE region_id = 0
                    AND user_type = ?
                    AND user_id = ?
                    AND country = ?
                    AND category = ?
                    LIMIT $amount",
                [$region_id, Port::TYPE_RESELLER, $id, $country, $category]);
        }
        
        $this->userLog(null, [$id, $country, $category, $locationSubmitted, $totalPorts]);
        
        return $this->getAction($id);
    }

    public function quickAssignAction(Request $request, $id)
    {
        $country = $request->get('country');
        $category = $request->get('category');
        $count = $request->get('count');
        $reseller = $this->getReseller();

        // Validate input
        $this->getResellerUser($id);
        $this->assertFalseOrException($reseller['credits'] <= 0, 'Reseller Out of Credits', [$id, $country, $category, $count]);
        $this->assertFalseOrException(!$country || !$category || !$count, 'Missing Country, Category or Count', [$id, $country, $category, $count]);
        $this->assertOrException(Port::isCountryValid($country), 'Country is not a valid option', [$id, $country, $category, $count], "Invalid Country");
        $this->assertOrException(Port::isCategoryValid($category), 'Category is not a valid option', [$id, $country, $category, $count], "Invalid Category");
        $this->assertFalseOrException($count <= 0, 'Count should be greater than Zero', [$id, $country, $category, $count], 'Invalid Count');

        $ports = $this->getConn('proxy')->fetchAll("
                SELECT *
                FROM user_ports
                WHERE user_type = ? AND user_id = ? AND country = ? AND category = ? AND proxy_ip = 0
                ORDER BY id
                LIMIT ?",
            [Port::TYPE_RESELLER, $id, $country, $category, (int) $count],
            [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT]
        );

        $rotationAdviser = new RotationAdviser($this->getConn('proxy'));
        foreach ($ports as $portData) {
            $port = Port::fromArray($portData)->setRotationAdviser($rotationAdviser);

            $newProxyId = $port->adviseNewProxyId();
            $this->userLog('new proxy id', ['id' => $newProxyId, 'obj' => [
                'country' => $port->getCountry(),
                'category' => $port->getCategory(),
                'user_id' => $port->getUserId(),
                'region_id' => $port->getRegionId(),
                'sneaker_location' => $port->getSneakerLocation()
            ]]);

            // Assign new port
            $this->getConn('proxy')
                ->update('user_ports', ['proxy_ip' => $newProxyId], ['id' => $port->getId()]);
        }

        return $this->getAction($id);
    }
    
    public function updatePlanAction(Request $request, $id)
    {   
        $country = $request->get('country');
        $category = Port::toOldCategory($request->get('category'));
        $count = intval($request->get('count'));
        // $days = $request->get('days');
        $expiration = $request->get('expiration');

        $reseller = $this->getReseller();

        // Validate input
        $this->assertFalseOrException($reseller['credits'] <= 0, 'Reseller Out of Credits', [$id, $country, $category, $count]);
        $this->assertFalseOrException(!$country || !$category || !$count, 'Missing Country, Category or Count', [$id, $country, $category, $count]);
        $this->assertOrException(OldResellerPort::isCategoryCountryAvailable(Port::toNewCategory($category), $country),
            'Country or category is not available', [$id, $country, $category, $count], 'Invalid Country/Category');
        $this->assertFalseOrException($count <= 0, 'Count should be greater than Zero', [$id, $country, $category, $count], 'Invalid Count');
        $this->getResellerUser($id);
        
        $sql = "INSERT INTO reseller_user_packages (reseller_user_id, country, category, count, created, expiration) 
            VALUES (?,?,?,?,NOW(),?) ON DUPLICATE KEY UPDATE count = ?, expiration = ?";
        $this->getConn('proxy')->executeUpdate($sql, [$id, $country, $category, $count, $expiration, $count, $expiration]);
        
        $plan = $this->getConn('proxy')->fetchAssoc("
            SELECT id as plan_id, reseller_user_id as user_id, country, category, count, expiration
            FROM reseller_user_packages
            WHERE reseller_user_id = ? AND country = ? AND category = ?", [
                $id, $country, $category
        ]);

        $plan['category'] = Port::toNewCategory($plan['category']);
        
        if ($plan['expiration']) {
            $plan['expiration'] = strtotime($plan['expiration']);
        }        
        
        $userQuery = "SELECT ru.id, rup.country, rup.category,  IFNULL(rup.count, 0) as package_ports, IFNULL(ports.count, 0) as user_ports
            FROM reseller_users ru
            JOIN reseller_user_packages rup ON rup.reseller_user_id = ru.id
            LEFT JOIN ( 
                SELECT user_id, country, category, count(*) as count 
                FROM user_ports
                WHERE user_type = ?
                GROUP BY user_id, country, category 
            ) as ports ON ru.id = ports.user_id and rup.country = ports.country and rup.category = ports.category
            WHERE IFNULL(rup.count, 0) != IFNULL(ports.count, 0)";
                
        $users = $this->getConn('proxy')->executeQuery($userQuery, [Port::TYPE_RESELLER])->fetchAll(\PDO::FETCH_OBJ);
        $assigner = new PortAssigner($this->getConn('proxy'));
        foreach($users as $user) {
            $port = CountedPort::construct()
                ->setUserId($user->id)
                ->setUserType(Port::TYPE_RESELLER)
                ->setCountry($user->country)
                ->setCategory($user->category)
                ->setTotalPortCount($user->package_ports)
                ->setActualPortCount($user->user_ports);

            $assigner->alignPortsCounted($port, true, false);
        }

        $this->userLog('null', [$id, $country, $category, $count, $plan]);

        return $plan;
    }
    
    public function updatePlanExpirationAction(Request $request, $id)
    {
        $country = $request->get('country');
        $category = Port::toOldCategory($request->get('category'));
        $expiration = $request->get('expiration');

        $this->assertFalseOrException(!$country || !$category, 'Missing Country or Category', [$id, $country, $category]);
        $this->assertOrException(Port::isCountryValid($country), 'Country is not a valid option', [$id, $country, $category], "Invalid Country");
        $this->assertOrException(Port::isCategoryValid($category), 'Category is not a valid option', [$id, $country, $category], "Invalid Category");
        
        if ($expiration !== null) {            
            if (intval($expiration) > time()) {
                $expiration = date('Y-m-d H:i:s', intval($expiration));
            } else {
                $this->throwException('Expiration is invalid', [$id, $country, $category, $expiration]);
            }
        } else {
            $expiration = null;
        }
        
        $this->getResellerUser($id);

        $sql = "UPDATE reseller_user_packages SET expiration = ? WHERE country = ? and category = ? and reseller_user_id = ?";
        $this->getConn('proxy')->executeUpdate($sql, [$expiration, $country, $category, $id]);
        
        $plan = $this->getConn('proxy')->fetchAssoc("
            SELECT id as plan_id, reseller_user_id as user_id, country, category, count, expiration
            FROM reseller_user_packages
            WHERE reseller_user_id = ? AND country = ? AND category = ?", [
                $id, $country, $category
        ]);

        // Skip old terminology
        $plan['category'] = OldResellerPort::toOldCategory($plan['category']);

        if ($plan['expiration']) {
            $plan['expiration'] = strtotime($plan['expiration']);
        }

        $this->userLog(null, [$id, $country, $category, $expiration, $plan]);

        return $plan;
    }
    
    public function deletePlanAction($id, $country, $category)
    {        
        $category = Port::toOldCategory($category);

        // Validate input
        $this->assertOrException(Port::isCountryValid($country), 'Country is not a valid option', [$id, $country, $category], "Invalid Country");
        $this->assertOrException(Port::isCategoryValid($category), 'Category is not a valid option', [$id, $country, $category], "Invalid Category");
        $this->getResellerUser($id);

        $plan = $this->getConn('proxy')->fetchAssoc("
            SELECT id as plan_id, reseller_user_id as user_id, country, category, count
            FROM reseller_user_packages
            WHERE reseller_user_id = ? AND country = ? AND category = ?", [
                $id, $country, $category
        ]);

        $this->assertOrException($plan, 'Plan does not exists', [$id, $country, $category], 'No Plan');

        $this->getConn('proxy')->update('reseller_user_packages',
            ['count' => 0],
            ['reseller_user_id' => $id, 'country' => $country, 'category' => $category]
        );
        
        $plan['count'] = 0;

        $plan['category'] = Port::toNewCategory($plan['category']);

        $this->userLog(null, [$id, $country, $category, $plan]);

        return $plan;
    }
    
    public function addIp(Request $request, $id)
    {
        $ip = $request->get('ip');

        // Validate input
        $this->assertOrException($ip, 'You need to pass an ip', [$id, $ip], 'No IP');
        $this->assertOrException(filter_var($ip, FILTER_VALIDATE_IP), 'Invalid IP Format', [$id, $ip], 'Invalid IP');
        $this->getResellerUser($id);

        $ipRecord = $this->getConn('proxy')->fetchAssoc("SELECT *
            FROM user_ips
            WHERE user_type = 'RS' and ip = ? and user_id = ?", [
                $ip, $id
            ]);

        $this->assertOrException(!$ipRecord, 'That IP is already associated with that user', [$id, $ip, $ipRecord], 'Ip Already Associated');

        $this->getConn('proxy')->insert('user_ips', [
            'user_type' => 'RS',
            'user_id' => $id,
            'ip' => $ip
        ]);
        
        $this->userLog(null, [$id, $ip]);

        return $this->getAction($id);
    }
    
    public function deleteIp(Request $request, $id)
    {
        $ip = $request->get('ip');

        // Validate input
        $this->getResellerUser($id);
        $this->assertOrException($ip, 'You need to pass an ip', [$id, $ip], 'No IP');
        $this->assertOrException(filter_var($ip, FILTER_VALIDATE_IP), 'Invalid IP Format', [$id, $ip], 'Invalid IP');
        
        $ipRecord = $this->getConn('proxy')->fetchAssoc("SELECT * 
            FROM user_ips
            WHERE user_type = 'RS' and ip = ? and user_id = ?", [
                $ip, $id
            ]);

        $this->assertOrException($ipRecord, 'That IP is not associated with that user', [$id, $ip, $ipRecord]);

        $this->getConn('proxy')->delete('user_ips', [
            'user_type' => 'RS',
            'user_id' => $id,
            'ip' => $ip
        ]);

        $this->userLog(null, [$id, $ip]);

        return $this->getAction($id);
    }

    // --- Helpers

    /**
     * Get reseller user or throw an exception
     *
     * @param $id
     * @throws \ErrorException
     * @return array
     */
    protected function getResellerUser($id)
    {
        $reseller = $this->getReseller();

        $resellerUser = $this->getConn('proxy')->fetchAll('SELECT *
            FROM reseller_users
            WHERE reseller_id = ? and id = ?', [
            $reseller['id'], $id
        ]);

        $this->assertOrException($resellerUser, 'There is no proxy user with that id', [$id], 'No Proxy User');

        return $resellerUser;
    }
}