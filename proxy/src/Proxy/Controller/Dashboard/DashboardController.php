<?php

namespace Proxy\Controller\Dashboard;

class DashboardController extends AbstractController
{
    public function indexAction()
    {
        $user = $this->getUser();

        if (!$user or !$this->getUser('admin')) {
            return $this->renderTemplate('ProxyDashboard/login/index.html.twig');
        }

        return $this->renderDefaultTemplate();
    }

    public function importIpsAction()
    {
        $added = 0;
        $updated = 0;

        if ('POST' == $this->request->getMethod()) {
            $proxies = array_filter(array_map('trim', explode("\n", $this->request->request->get('proxies', ''))));
            $validated = true;

            if (!$proxies) {
                $this->addFlashError('No IPs / blocks are passed');
                $validated = false;
            }
            if (!$this->request->request->get('proxy-type')) {
                $this->addFlashError('No type is selected');
                $validated = false;
            }
            if (!$this->request->request->get('proxy-country')) {
                $this->addFlashError('No country is selected');
                $validated = false;
            }
            if (!$this->request->request->get('proxy-source')) {
                $this->addFlashError('No source is selected');
                $validated = false;
            }

            if ($validated and count($proxies)) {
                $active = 0;
                $static = $this->request->request->get('proxy-type', 'static') == 'static' ? 1 : 0;
                $country = strtolower($this->request->request->get('proxy-country'));
                $source = $this->request->request->get('proxy-source');
                $region = $this->request->request->get('proxy-region');
                $host = strtolower($this->request->request->get('proxy-host'));
                $org = strtolower($this->request->request->get('proxy-org'));
                $hostLoc = strtolower($this->request->request->get('proxy-host-loc'));
                $extra = $this->request->request->get('proxy-extra') ? true : false;
                $sourceName = $this->request->request->get('proxy-source-name');

                if ($region) {
                    $query = "SELECT id FROM proxy_regions WHERE country = ? and region = ?";
                    $rs = $this->getConn()->executeQuery($query, [$country, $region]);
                    if ($region_res = $rs->fetch()) {
                        $region_id = $region_res[ 'id' ];
                    }
                    else {
                        $query = "INSERT INTO proxy_regions (country, region) VALUES(?, ?)";
                        $this->getConn()->executeQuery($query, [$country, $region]);
                        $region_id = $this->getConn()->lastInsertId();
                    }
                }
                else {
                    $region_id = 1;
                }

                if ($sourceName) {
                    $query = "UPDATE proxy_source SET name = ? WHERE id = ?";
                    $this->getConn()->executeQuery($query, [$sourceName, $source]);
                }

                if (!$sourceName) {
                    $query = "SELECT name FROM proxy_source WHERE id = ?";
                    $rs = $this->getConn()->executeQuery($query, [$source]);
                    if ($source_res = $rs->fetch()) {
                        $sourceName = $source_res[ 'name' ];
                    }
                }

                $this->getConn()->beginTransaction();

                foreach ($proxies as $proxy) {
                    $proxy = explode(":", trim($proxy));
                    $mask = (isset($proxy[ 2 ])) ? $proxy[ 2 ] : '255.255.255.255';
                    $port = (isset($proxy[ 1 ])) ? $proxy[ 1 ] : '3128';
                    if (filter_var($proxy[ 0 ], FILTER_VALIDATE_IP)) {
                        $query = "
                            INSERT INTO proxies_ipv4 
								SET active = :active, 
                                    date_added = :now, 
									ip = :ip, 
									port = :port, 
									static = :static, 
									country = :country,
									region_id = :regionId,
									source_id = :sourceId,
									source = :sourceName,                                    
									mask = :mask,
                                    host = :host,
                                    host_loc = :hostLoc,
                                    organization = :org,
									pristine = 1,
									new = 1
                            ON DUPLICATE KEY UPDATE 
                                new = 1,
                                static = :static,
                                country = :country,
                                region_id = :regionId,
                                source_id = :sourceId,
                                source = :sourceName,
                                mask = :mask,
                                host = :host,
                                host_loc = :hostLoc,
                                organization = :org";
                        $affected = $this->getConn()->executeUpdate($query, [
                            'active'     => $active,
                            'now'        => date('Y-m-d H:i:s'),
                            'ip'         => $proxy[ 0 ],
                            'port'       => $port,
                            'static'     => $static,
                            'country'    => $country,
                            'regionId'   => $region_id,
                            'sourceId'   => $source,
                            'sourceName' => $sourceName,
                            'mask'       => $mask,
                            'host'       => $host,
                            'hostLoc'    => $hostLoc,
                            'org'        => $org
                        ]);

                        if ($affected == 1) {
                            $added += 1;
                        }
                        else {
                            if ($affected == 2) {
                                $updated += 1;
                            }
                        }

                    }
                    else {
                        //Importing a Slash
                        if (strpos($proxy[ 0 ], "/") !== false) {
                            $slash = explode("/", trim($proxy[ 0 ]));
                            $import = 0;

                            if ($slash[ 1 ] == '24') {
                                $mask = '255.255.255.0';
                                $import = 253;
                            }
                            elseif ($slash[ 1 ] == '25') {
                                $mask = '255.255.255.128';
                                $import = 125;
                            }
                            elseif ($slash[ 1 ] == '26') {
                                $mask = '255.255.255.192';
                                $import = 61;
                            }
                            elseif ($slash[ 1 ] == '27') {
                                $mask = '255.255.255.224';
                                $import = 29;
                            }
                            elseif ($slash[ 1 ] == '28') {
                                $mask = '255.255.255.240';
                                $import = 13;
                            }
                            elseif ($slash[ 1 ] == '29') {
                                $mask = '255.255.255.248';
                                $import = 5;
                            }
                            elseif ($slash[ 1 ] == '30') {
                                $mask = '255.255.255.252';
                                $import = 1;
                            }
                            elseif ($slash[ 1 ] == '31') {
                                $mask = '255.255.255.255';
                                $import = -1;
                            }
                            elseif ($slash[ 1 ] == '32') {
                                $mask = '255.255.255.255';
                                $import = -2;
                            }
                            if ($extra) {
                                $import += 3;
                                $mask = '255.255.255.255';
                            }
                            if ($import > 0 && filter_var($slash[ 0 ], FILTER_VALIDATE_IP)) {
                                if ($extra) {
                                    $start = ip2long($slash[ 0 ]); // Germany
                                }
                                else {
                                    $start = ip2long($slash[ 0 ]) + 2;
                                }

                                $end = $start + $import - 1;
                                for ($i = $start; $i <= $end; $i++) {
                                    $ip = long2ip($i);

                                    $query = "INSERT INTO `proxies_ipv4`                           
											SET active = :active, 
                                                date_added = :now, 
                                                ip = :ip, 
                                                port = :port, 
                                                static = :static, 
                                                country = :country,
                                                region_id = :regionId,
                                                source_id = :sourceId,
                                                source = :sourceName,                                    
                                                mask = :mask,
                                                host = :host,
                                                host_loc = :hostLoc,
                                                organization = :org,
                                                pristine = 1,
                                                new = 1,
                                                block = :block
                                            ON DUPLICATE KEY UPDATE 
                                                new = 1,
                                                static = :static,
                                                country = :country,
                                                region_id = :regionId,
                                                source_id = :sourceId,
                                                source = :sourceName,
                                                mask = :mask,
                                                host = :host,
                                                host_loc = :hostLoc,
                                                organization = :org,
                                                block = :block,
                                                date_updated = :now";
                                    $affected = $this->getConn()->executeUpdate($query, [
                                        'active'     => $active,
                                        'now'        => date('Y-m-d H:i:s'),
                                        'ip'         => $ip,
                                        'port'       => $port,
                                        'static'     => $static,
                                        'country'    => $country,
                                        'regionId'   => $region_id,
                                        'sourceId'   => $source,
                                        'sourceName' => $sourceName,
                                        'mask'       => $mask,
                                        'host'       => $host,
                                        'hostLoc'    => $hostLoc,
                                        'org'        => $org,
                                        'block'      => $proxy[ 0 ]
                                    ]);

                                    if ($affected == 1) {
                                        $added += 1;
                                    }
                                    else {
                                        if ($affected == 2) {
                                            $updated += 1;
                                        }
                                    }
                                }

                                $this->addFlashSuccess("Block \"{$proxy[ 0 ]}\" has been processed");
                                $this->logger->debug("Block \"{$proxy[ 0 ]}\" is imported", [
                                    'block'      => $proxy[ 0 ],
                                    'static'     => $static,
                                    'country'    => $country,
                                    'regionId'   => $region_id,
                                    'sourceId'   => $source,
                                    'sourceName' => $sourceName,
                                    'mask'       => $mask,
                                    'host'       => $host,
                                    'hostLoc'    => $hostLoc,
                                    'org'        => $org,
                                ]);
                            }
                        }
                    }
                }

                $this->getConn()->commit();

                if ($added) {
                    $this->addFlashSuccess('Imported IPs: ' . $added);
                    $this->logger->debug('Imported IPs: ' . $added, ['request' => $this->request->request->all()]);
                }
                if ($updated) {
                    $this->addFlashSuccess('Updated IPs: ' . $updated);
                    $this->logger->debug('Updated IPs: ' . $added, ['request' => $this->request->request->all()]);
                }
            }
        }

        $data = [
            'proxies' => ($added or $updated) ? '' : trim($this->request->request->get('proxies')),
            'proxyType' => $this->request->request->get('proxy-type'),
            'country' => strtolower($this->request->request->get('proxy-country')),
            'source' => $this->request->request->get('proxy-source'),
            'sourceName' => $this->request->request->get('proxy-source-name'),
            'region' => $this->request->request->get('proxy-region'),
            'host' => strtolower($this->request->request->get('proxy-host')),
            'org' => strtolower($this->request->request->get('proxy-org')),
            'hostLoc' => strtolower($this->request->request->get('proxy-host-loc')),
            'extra' => $this->request->request->get('proxy-extra', null),
        ];

        foreach ($this->getConn()->fetchAll('SELECT * FROM proxy_source ORDER BY name') as $source) {
            $data['sources'][$source['id']] = [
                'name' => $source['name'],
                'title' => $source['name'] ? "{$source['name']} - {$source['ip']}" : $source['ip']
            ];
        }

        return $this->renderDefaultTemplate($data);
    }
}