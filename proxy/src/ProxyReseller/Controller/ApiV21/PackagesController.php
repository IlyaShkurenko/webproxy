<?php

namespace ProxyReseller\Controller\ApiV21;

use Proxy\Assignment\PackageDict;
use Proxy\Assignment\Port\IPv4;
use Proxy\Assignment\Port\IPv6;
use ProxyReseller\Controller\ApiV20\PackagesController as BaseController;
use ProxyReseller\Controller\ApiV21\Traits\CommonMethodsTrait;
use ProxyReseller\Exception\ApiException;

class PackagesController extends BaseController
{
    use CommonMethodsTrait;

    public function getAllAction($userId, $ipVersion = false, $source = false, $status = false)
    {
        $user = $this->getUser($userId);
        $this->validateIpVersion($ipVersion, true);

        $qb = $this->getConn()->createQueryBuilder();
        $qb->from('proxy_user_packages')
            ->select('ports', 'type', 'ext', 'source', 'id', 'package_id', 'status', 'ip_v')
            ->where('user_id = ' . $qb->createNamedParameter($user['id']));
        if (IPv4\Port::INTERNET_PROTOCOL == $ipVersion or !$ipVersion) {
            $qb->addSelect('country', 'category');
        }
        if (in_array($ipVersion, [IPv4\Port::INTERNET_PROTOCOL, IPv6\Package::INTERNET_PROTOCOL])) {
            $qb->andWhere('ip_v = ' . $qb->createNamedParameter($ipVersion));
        }
        if ($source) {
            $this->validateBillingSource($source);
            $qb->andWhere('source = ' . $qb->createNamedParameter($source));
        }
        if ($status) {
            $this->validateStatus($status);
            $qb->andWhere('status = ' . $qb->createNamedParameter($status));
        }

        return [
            'list' => array_map([$this, 'remapPackageRow'], $qb->execute()->fetchAll())
        ];
    }

    public function deleteAllAction($userId, $ipVersion = false, $source = false)
    {
        $user = $this->getUser($userId);
        $this->validateIpVersion($ipVersion, true);

        $where = ['user_id' => $user[ 'id' ]];
        if ($source) {
            $where[ 'source' ] = $source;
        }
        if ($ipVersion) {
            $where[ 'ip_v' ] = $ipVersion;
        }
        $removed = $this->getConn()->delete('proxy_user_packages', $where);

        return [
            'status' => 'ok',
            'count'    => $removed,
            'list' => $this->getAllAction($userId, $ipVersion, $source)['list']
        ];
    }

    public function getByAttributesAction(
        $userId,
        $ipVersion,
        $type = null,
        $country = null,
        $category = null,
        $source = false
    )
    {
        // Validate attributes
        $user = $this->getUser($userId);
        $this->validateIpVersion($ipVersion);
        if (IPv4\Port::INTERNET_PROTOCOL == $ipVersion) {
            $this->assertOrException(!empty($country) and !empty($category),
                'Either "country" or "category" parameter cannot be empty'
            );

            if (!$type) {
                $type = IPv4\Port::PACKAGE_TYPE_SINGLE;
            }
        }
        else {
            $this->assertOrException(!empty($type), 'Type cannot be empty');
        }

        // Build query
        $qb = $this->getConn()->createQueryBuilder();
        $qb->from('proxy_user_packages')
            ->select('ports', 'type', 'ext', 'source', 'id', 'package_id', 'status', 'ip_v')
            ->where(
                'user_id = ' . $qb->createNamedParameter($user['id']),
                'type = ' . $qb->createNamedParameter($type)
            );
        $qb->setMaxResults(1);

        // Specific conditions
        if (IPv4\Port::INTERNET_PROTOCOL == $ipVersion) {
            $qb->addSelect('country', 'category');
            $qb->andWhere(
                'country = ' . $qb->createNamedParameter($country),
                'category = ' . $qb->createNamedParameter(IPv4\Port::toOldCategory($category))
            );
        }
        if ($source) {
            $this->validateBillingSource($source);
            $qb->andWhere('source = ' . $qb->createNamedParameter($source));
        }

        $item = $qb->execute()->fetch();
        $this->assertOrException($item, 'No package exists', [], 'NOT_FOUND', ApiException::LOG_INFO);

        return [
            'item' => $this->remapPackageRow($item)
        ];
    }

    public function getByIdAction($id, $userId)
    {
        $user = $this->getUser($userId);

        // Build query
        $qb = $this->getConn()->createQueryBuilder();
        $qb->from('proxy_user_packages')
            ->select('ports', 'type', 'ext', 'source', 'id', 'package_id', 'status', 'ip_v', 'country', 'category')
            ->where(
                'id = ' . $qb->createNamedParameter($id),
                'user_id = ' . $qb->createNamedParameter($user['id'])
            );
        $qb->setMaxResults(1);

        $item = $qb->execute()->fetch();
        $this->assertOrException($item, 'No package exists', [], 'NOT_FOUND', ApiException::LOG_INFO);

        return [
            'item' => $this->remapPackageRow($item)
        ];
    }

    public function add2Action(
        $userId,
        $ipVersion,
        $type = null,
        $ext = null,
        $country = null,
        $category = null,
        $ports,
        $source,
        $status = PackageDict::STATUS_ACTIVE
    )
    {
        // Validation
        $user = $this->getUser($userId);
        $this->validateBillingSource($source);
        $this->validateStatus($status);
        $this->validateIpVersion($ipVersion);

        // Specific validation
        if (IPv4\Port::INTERNET_PROTOCOL == $ipVersion) {
            $this->assertOrException(IPv4\Port::isCategoryCountryAvailable($category, $country),
                "\"$country-$category\" is not available currently"
            );
            $allPackages = $this->getAllAction($userId, false, $source);
            foreach ($allPackages[ 'list' ] as $package) {
                $this->assertFalseOrException($package[ 'country' ] == $country and
                    $package[ 'category' ] == $category,
                    "Can not add package as \"$country-$category\" package already exists");
            }

            if (!$type) {
                $type = IPv4\Port::PACKAGE_TYPE_SINGLE;
            }
        }
        else {
            $scheme = [
                IPv6\Package::TYPE_BLOCK_N_PER_BLOCK => [
                    'perSubnet' => [2],
                    'subnet' => [56]
                ]
            ];
            $this->assertOrException(in_array($type, array_keys($scheme)),
                'Parameter "type" is incorrect, allowed values are: ' . join(', ', array_keys($scheme))
            );
            // Validate against scheme
            if ($scheme[$type]) {
                $this->assertOrException($ext, 'Parameter "ext" cannot be empty');
                foreach ($scheme[$type] as $parameter => $value) {
                    $this->assertFalseOrException(
                        empty($ext[$parameter]) or !in_array($ext[$parameter], $value),
                        "Parameter ext.$parameter is empty or invalid, allowed values are: " . join(', ', $value)
                    );
                }
            }
        }

        $this->getConn()->insert('proxy_user_packages', [
            'user_id'    => $user[ 'id' ],
            'ip_v'       => $ipVersion,
            'source'     => $source,
            'type'       => $type,
            'ext'        => $ext ? json_encode($ext) : null,
            'country'    => $country,
            'category'   => IPv4\Port::toOldCategory($category),
            'status'     => $status ? $status : PackageDict::STATUS_ACTIVE,
            'ports'      => $ports,
            'created'    => date('Y-m-d H:i:s'),
            'package_id' => 1,
        ]);

        return [
            'status' => 'ok',
            'count'  => 1,
            'item' => $this->getByIdAction($this->getConn()->lastInsertId(), $userId)['item'],
            'list' => $this->getAllAction($userId)[ 'list' ]
        ];
    }

    public function updateById($userId, $id, $ports, $status = false)
    {
        $user = $this->getUser($userId);
        if ($status) {
            $this->validateStatus($status);
        }
        $this->assertOrException($ports or $status, 'Either "ports" or "status" is required');
        $exists = $this->getConn()->executeQuery(
            'SELECT id FROM proxy_user_packages WHERE user_id = ? AND id = ?',
            [$user['id'], $id]
        )->fetchColumn();
        $this->assertOrException($exists, "Can not update package as no package with \"$id\" id exists");

        $this->getConn()->update('proxy_user_packages', array_merge($ports ? [
            'ports' => $ports
        ] : [], $status ? [
            'status' => $status
        ] : []), [
            'user_id'  => $user[ 'id' ],
            'id'  => $id,
        ]);

        return [
            'status' => 'ok',
            'count'  => 1,
            'list' => $this->getAllAction($userId)[ 'list' ]
        ];
    }

    public function updateByAttributesAction($userId, $source, $country, $category, $ports, $status = false)
    {
        $user = $this->getUser($userId);
        $this->validateBillingSource($source);
        if ($status) {
            $this->validateStatus($status);
        }
        $allPackages = $this->getAllAction($userId, false, $source);

        $exists = false;
        foreach ($allPackages[ 'list' ] as $package) {
            if ($package[ 'country' ] == $country and $package[ 'category' ] == $category
                and $package[ 'source' ] == $source
            ) {
                $exists = true;
            }
        }
        $this->assertOrException($exists, "Can not update package as no \"$country-$category\" (\"$source\") exists");

        $this->getConn()->update('proxy_user_packages', array_merge([
            'ports' => $ports
        ], $status ? [
            'status' => $status
        ] : []), [
            'user_id'  => $user[ 'id' ],
            'country'  => $country,
            'category' => IPv4\Port::toOldCategory($category),
            'source'   => $source
        ]);

        return [
            'status' => 'ok',
            'count'  => 1,
            'list' => $this->getAllAction($userId)[ 'list' ]
        ];
    }

    public function deleteByIdAction($userId, $id)
    {
        $user = $this->getUser($userId);
        $exists = $this->getConn()->executeQuery(
            'SELECT id FROM proxy_user_packages WHERE user_id = ? AND id = ?',
            [$user['id'], $id]
        )->fetchColumn();
        $this->assertOrException($exists, "Can not update package as no package with \"$id\" id exists");

        $this->getConn()->delete('proxy_user_packages', [
            'user_id' => $user[ 'id' ],
            'id'      => $id,
        ]);

        return [
            'status' => 'ok',
            'count'  => 1,
            'list' => $this->getAllAction($userId)[ 'list' ]
        ];
    }

    public function deleteByAttributesAction($userId, $source, $country, $category)
    {
        $user = $this->getUser($userId);
        $this->validateBillingSource($source);
        $allPackages = $this->getAllAction($userId, false, $source);

        $exists = false;
        foreach ($allPackages[ 'list' ] as $package) {
            if ($package[ 'country' ] == $country and $package[ 'category' ] == $category
                and $package[ 'source' ] == $source
            ) {
                $exists = true;
            }
        }
        $this->assertOrException($exists, "Can not remove package as no \"$country-$category\" (\"$source\") exists");

        $this->getConn()->delete('proxy_user_packages', [
            'user_id'  => $user[ 'id' ],
            'country'  => $country,
            'category' => IPv4\Port::toOldCategory($category),
            'source'   => $source
        ]);

        return [
            'status' => 'ok',
            'count'  => 1,
            'list' => $this->getAllAction($userId)[ 'list' ]
        ];
    }

    protected function remapPackageRow(array $row)
    {
        $map = [
            'ip_v' => 'ipVersion'
        ];

        // Initial preparations
        $row = $this->mapPackageIdToPorts($row);
        if (!empty($row['category'])) {
            $row['category'] = IPv4\Port::toNewCategory($row['category']);
        }
        elseif (empty($row['country'])) {
            // Both country and category are empty
            unset($row['country'], $row['category']);
        }
        if (!empty($row['ext'])) {
            try {
                $ext = json_decode($row['ext'], true);
                $row['ext'] = $ext;
            }
            catch (\Exception $e) {}
        }

        // Map attributes
        foreach ($map as $from => $to) {
            if (isset($row[$from])) {
                $row[$to] = $row[$from];
                unset($row[$from]);
            }
        }

        return $row;
    }
}
