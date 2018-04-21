<?php

namespace ProxyReseller\Controller\ApiV20;

use Axelarge\ArrayTools\Arr;
use Proxy\Assignment\PackageDict;
use Proxy\Assignment\Port\IPv4;
use ProxyReseller\Controller\AbstractVersionedController;
use ProxyReseller\Controller\ApiV20\Traits\CommonMethodsTrait;
use ProxyReseller\Exception\ApiException;

class PackagesController extends AbstractVersionedController
{
    use CommonMethodsTrait;

    public function getAllAction($userId, $source = false, $status = false)
    {
        $user = $this->getUser($userId);

        $qb = $this->getConn()->createQueryBuilder();
        $qb->from('proxy_user_packages')
            ->select('country', 'category', 'ports', 'source', 'id', 'package_id', 'status')
            ->where('user_id = ' . $qb->createNamedParameter($user['id']));
        if ($source) {
            $this->validateBillingSource($source);
            $qb->andWhere('source = ' . $qb->createNamedParameter($source));
        }
        if ($status) {
            $this->validateStatus($status);
            $qb->andWhere('status = ' . $qb->createNamedParameter($status));
        }

        return [
            'list' => array_map(function(array $row) {
                $row = $this->mapPackageIdToPorts($row);
                $row['category'] = IPv4\Port::toNewCategory($row['category']);

                return $row;
            }, $qb->execute()->fetchAll())
        ];
    }

    public function deleteAllAction($userId, $source = false)
    {
        $user = $this->getUser($userId);
        $allPackages = $this->getAllAction($userId, $source);

        $where = ['user_id' => $user[ 'id' ]];
        if ($source) {
            $where[ 'source' ] = $source;
        }
        $removed = $this->getConn()->delete('proxy_user_packages', $where);

        return [
            'status' => 'ok',
            'count'    => $removed,
            'list' => $allPackages[ 'list' ]
        ];
    }

    public function getAction($userId, $country, $category, $source = false)
    {
        $user = $this->getUser($userId);

        $qb = $this->getConn()->createQueryBuilder();
        $qb->from('proxy_user_packages')
            ->select('country', 'category', 'ports', 'source', 'id', 'package_id', 'status')
            ->where(
                'user_id = ' . $qb->createNamedParameter($user['id']),
                'country = ' . $qb->createNamedParameter($country),
                'category = ' . $qb->createNamedParameter(IPv4\Port::toOldCategory($category))
            );
        $qb->setMaxResults(1);

        if ($source) {
            $this->validateBillingSource($source);
            $qb->andWhere('source = ' . $qb->createNamedParameter($source));
        }

        $item = $qb->execute()->fetch();
        $this->assertOrException($item, 'No package exists', [], 'NOT_FOUND', ApiException::LOG_INFO);
        $item = $this->mapPackageIdToPorts($item);

        return [
            'item' => $item
        ];
    }

    public function addAction($userId, $source, $country, $category, $ports, $status = PackageDict::STATUS_ACTIVE)
    {
        $user = $this->getUser($userId);
        $this->validateBillingSource($source);
        $this->validateStatus($status);
        $allPackages = $this->getAllAction($userId, $source);

        $this->assertOrException(IPv4\Port::isCategoryCountryAvailable($category, $country),
            "\"$country-$category\" is not available currently"
        );

        foreach ($allPackages[ 'list' ] as $package) {
            $this->assertFalseOrException($package[ 'country' ] == $country and
                $package[ 'category' ] == $category,
                "Can not add package as \"$country-$category\" package already exists");
        }

        $this->getConn()->insert('proxy_user_packages', [
            'user_id'    => $user[ 'id' ],
            'source'     => $source,
            'country'    => $country,
            'category'   => IPv4\Port::toOldCategory($category),
            'status'     => $status ? $status : 'active',
            'ports'      => $ports,
            'created'    => date('Y-m-d H:i:s'),
            'package_id' => 1,
        ]);

        return [
            'status' => 'ok',
            'count'  => 1,
            'list' => $this->getAllAction($userId)[ 'list' ]
        ];
    }

    public function updateAction($userId, $source, $country, $category, $ports, $status = false)
    {
        $user = $this->getUser($userId);
        $this->validateBillingSource($source);
        if ($status) {
            $this->validateStatus($status);
        }
        $allPackages = $this->getAllAction($userId, $source);

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

    public function deleteAction($userId, $source, $country, $category)
    {
        $user = $this->getUser($userId);
        $this->validateBillingSource($source);
        $allPackages = $this->getAllAction($userId, $source);

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

    protected function mapPackageIdToPorts(array $package)
    {
        if (empty($package[ 'ports' ]) and !empty($package[ 'package_id' ])) {
            static $packages;
            if (!$packages) {
                $packages = $this->getConn()->createQueryBuilder()
                    ->select('ports', 'id')
                    ->from('proxy_packages')
                    ->execute()
                    ->fetchAll();
                $packages = Arr::pluck($packages, 'ports', 'id');
            }

            if (!empty($packages[ $package[ 'package_id' ] ])) {
                $package[ 'ports' ] = $packages[ $package[ 'package_id' ] ];
            }
        }

        unset($package[ 'package_id' ]);
        $package['ports'] = (int) $package['ports'];

        return $package;
    }

    protected function validateStatus($status)
    {
        $this->assertOrException(in_array($status, [PackageDict::STATUS_ACTIVE, PackageDict::STATUS_SUSPENDED]),
            sprintf('Status can be only %s or %s', PackageDict::STATUS_ACTIVE, PackageDict::STATUS_SUSPENDED));
    }
}
