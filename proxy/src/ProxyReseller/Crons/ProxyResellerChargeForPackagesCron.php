<?php

namespace ProxyReseller\Crons;

use Axelarge\ArrayTools\Arr;
use Doctrine\DBAL\Connection;
use Proxy\Assignment\Port\IPv4\Port;
use Reseller\Helper\PricingHelper;

class ProxyResellerChargeForPackagesCron extends AbstractCron
{
    protected $config = [
        'schedule' => '* * * * *'
    ];

    protected $settings = [
        'chargeCycle' => 30,
        'chargeDelay' => 60 * 60 * 12
    ];

    protected $cache = [];

    public function run()
    {

        // Remove expired tracked packages (cancelled in the prev cycle)
        $packages = $this->getConn()->executeQuery(
            "SELECT rtp.*
            FROM reseller_tracked_packages rtp
            LEFT JOIN proxy_user_packages pup ON pup.id = rtp.package_id
            WHERE pup.id IS NULL", [])->fetchAll();
        foreach ($packages as $package) {
            $this->log("Removing package of reseller's {$package['reseller_id']} package " .
                "{$package['country']}-{$package['category']} x {$package['ports']}", $package, [
                'resellerId' => $package[ 'reseller_id' ],
                'userId'     => $package[ 'user_id' ]
            ]);
        }
        $this->getConn()->executeQuery('DELETE FROM reseller_tracked_packages WHERE id IN (?)',
            [Arr::pluck($packages, 'id')], [Connection::PARAM_INT_ARRAY]);

        // Add new
        $packages = $this->getConn()->executeQuery(
            "SELECT pup.*, pu.reseller_id
            FROM proxy_user_packages pup
            LEFT JOIN reseller_tracked_packages rtp ON pup.id = rtp.package_id
            INNER JOIN proxy_users pu ON pu.id = pup.user_id
            WHERE rtp.id IS NULL AND pup.created > :createdAt AND pup.ports > 0 AND pup.ip_v = :ipv4",
            ['createdAt' => time() - 60 * 60 * 24 * $this->getSetting('chargeCycle'), 'ipv4' => Port::INTERNET_PROTOCOL]
        )->fetchAll();
        foreach ($packages as $package) {
            $totalPorts = $this->getTotalPorts($package[ 'reseller_id' ], $package['country'], $package['category']);

            $priceData = $this->calculatePrice(
                $package[ 'reseller_id' ],
                $package[ 'country' ],
                $package[ 'category' ],
                $totalPorts + $package['ports'],
                strtotime($package['created'])
            );

            if (!$priceData) {
                $this->log("Price is not calculated for reseller's {$package['reseller_id']} package " .
                    "{$package['country']}-{$package['category']} x {$package['ports']} on adding new", $package, [
                    'resellerId' => $package[ 'reseller_id' ],
                    'userId'     => $package[ 'user_id' ]
                ]);
                continue;
            }

            $total = $priceData['price'] * $package['ports'];

            $this->log("New reseller's {$package['reseller_id']} package " .
                "{$package['country']}-{$package['category']} x {$package['ports']}",
                array_merge($package, [
                    'price'              => $priceData[ 'price' ],
                    'total'              => $total,
                    'totalResellerPorts' => $totalPorts + $package['ports'],
                    'balance'            => $this->getBalance($package[ 'reseller_id' ]) - $total,
                    'dueDate'            => date('Y-m-d H:i:s', $priceData[ 'dueDate' ]),
                    'formula'            => "({$priceData['formula']}) x {$package['ports']}"
                ]), [
                    'resellerId' => $package[ 'reseller_id' ],
                    'userId'     => $package[ 'user_id' ]
                ]);

            $this->getConn()->insert('reseller_tracked_packages', [
                'package_id'         => $package[ 'id' ],
                'reseller_id'        => $package[ 'reseller_id' ],
                'user_id'            => $package[ 'user_id' ],
                'country'            => $package[ 'country' ],
                'category'           => $package[ 'category' ],
                'ports'              => $package[ 'ports' ],
                'date_added'         => $package[ 'created' ],
                'date_charged'       => date('Y-m-d H:i:s'),
                'date_charged_until' => date('Y-m-d H:i:s', $priceData[ 'dueDate' ]),
            ]);

            $this->updateBalance($package[ 'reseller_id' ], -$total);
        }

        // Upgrade/downgrade for currents
        $packages = $this->getConn()->executeQuery(
            "SELECT rtp.*, rtp.ports as ports_prev, pup.ports as ports_actual
            FROM reseller_tracked_packages rtp
            INNER JOIN proxy_user_packages pup ON pup.id = rtp.package_id
            WHERE rtp.date_charged_until > ? AND rtp.ports != pup.ports",
            [date('Y-m-d H:i:s', time() - $this->getSetting('chargeDelay'))])
            ->fetchAll();
        foreach ($packages as $package) {
            $totalPortsPrev = $this->getTotalPorts($package[ 'reseller_id' ], $package['country'], $package['category']);
            $totalPortsActual = $totalPortsPrev + ($package['ports_actual'] - $package['ports_prev']);

            $priceDataLoan = $this->calculatePrice(
                $package[ 'reseller_id' ],
                $package[ 'country' ],
                $package[ 'category' ],
                $totalPortsPrev,
                strtotime($package['date_charged_until'])
            );
            $priceDataFull = $this->calculatePrice(
                $package[ 'reseller_id' ],
                $package[ 'country' ],
                $package[ 'category' ],
                $totalPortsPrev
            );

            // Adjust credits if less than 12 hours
            if (round(((strtotime($package[ 'date_charged_until' ]) - time()) / (60 * 60 * 24))) <= 0) {
                $priceDataLoan[ 'formula' ] = $priceDataLoan[ 'price' ] = 0;
            }

            if (!$priceDataLoan) {
                $this->log("Price is not calculated for reseller's {$package['reseller_id']} package " .
                    "{$package['country']}-{$package['category']} x {$package['ports_prev']} on upgrade/downgrade", $package, [
                    'resellerId' => $package[ 'reseller_id' ],
                    'userId'     => $package[ 'user_id' ]
                ]);
                continue;
            }

            $formula = $priceDataFull['price'] != $priceDataLoan['price'] ?
                $priceDataLoan ['formula'] :
                $priceDataFull['price'];
            $priceLoan = $priceDataFull['price'] != $priceDataLoan['price'] ?
                $priceDataLoan['price'] :
                $priceDataFull['price'];

            // Downgrade
            if ($package['ports_actual'] < $package['ports_prev']) {
                $newPriceData = $this->calculatePrice(
                    $package[ 'reseller_id' ],
                    $package[ 'country' ],
                    $package[ 'category' ],
                    $totalPortsActual,
                    strtotime($package['date_charged_until'])
                );

                $formula =
                    // Loan
                    "($formula) x {$package['ports_prev']}" .
                    // Debt
                    " - ({$newPriceData['formula']}) x {$package['ports_actual']}";
                $loan = abs($priceLoan * $package['ports_prev']);
                $debt = $newPriceData['price'] * $package['ports_actual'];
                $total = $this->plus($loan, -$debt);

                $this->log("Downgrade for reseller's {$package['reseller_id']} package " .
                    "{$package['country']}-{$package['category']} x {$package['ports_prev']}",
                    array_merge($package, [
                        'price'              => $priceDataFull[ 'price' ],
                        'totalResellerPorts' => $totalPortsActual,
                        'totalLoan'          => $loan,
                        'totalDebt'          => $debt,
                        'total'              => $total,
                        'balance'            => $this->plus($this->getBalance($package[ 'reseller_id' ]), $total),
                        'dueDate'            => date('Y-m-d H:i:s', $priceDataLoan[ 'dueDate' ]),
                        'formula'            => $formula
                    ]), [
                        'resellerId' => $package[ 'reseller_id' ],
                        'userId'     => $package[ 'user_id' ]
                    ]);

                $this->getConn()->update('reseller_tracked_packages',
                    [
                        'ports' => $package['ports_actual'],
                        'date_charged_until' => date('Y-m-d H:i:s', $priceDataLoan[ 'dueDate' ])
                    ], ['id' => $package['id']]);
                $this->updateBalance($package[ 'reseller_id' ], $total);
            }
            // Upgrade
            else {
                $newPriceData = $this->calculatePrice(
                    $package[ 'reseller_id' ],
                    $package[ 'country' ],
                    $package[ 'category' ],
                    $totalPortsActual,
                    time()
                );

                if (!$newPriceData) {
                    $this->log("Price is not calculated for reseller's {$package['reseller_id']} package " .
                        "{$package['country']}-{$package['category']} x {$package['ports_prev']} on upgrade", $package, [
                        'resellerId' => $package[ 'reseller_id' ],
                        'userId'     => $package[ 'user_id' ]
                    ]);
                    continue;
                }

                $formula =
                    // Loan
                    "-($formula) x {$package['ports_prev']}" .
                    // Debt
                    " + ({$newPriceData['formula']}) x {$package['ports_actual']}";
                $loan = abs($priceLoan * $package['ports_prev']);
                $debt = $newPriceData['price'] * $package['ports_actual'];
                $total = $this->plus(-$loan, $debt);

                $this->log("Upgrade for reseller's {$package['reseller_id']} package " .
                    "{$package['country']}-{$package['category']} x {$package['ports']}",
                    array_merge($package, [
                        'price'              => $priceDataFull[ 'price' ],
                        'totalResellerPorts' => $totalPortsActual,
                        'totalLoan'          => $loan,
                        'totalDebt'          => $debt,
                        'total'              => $total,
                        'balance'            => $this->plus($this->getBalance($package[ 'reseller_id' ]), -$total),
                        'dueDate'            => date('Y-m-d H:i:s', $newPriceData[ 'dueDate' ]),
                        'formula'            => $formula,
                    ]), [
                        'resellerId' => $package[ 'reseller_id' ],
                        'userId'     => $package[ 'user_id' ]
                    ]);

                $this->getConn()->update('reseller_tracked_packages',
                    [
                        'ports'              => $package[ 'ports_actual' ],
                        'date_charged_until' => date('Y-m-d H:i:s', $newPriceData[ 'dueDate' ])
                    ], ['id' => $package['id']]);
                $this->updateBalance($package[ 'reseller_id' ], -$total);
            }
        }

        // Recurring payment or upgrade/downgrade for expired
        $packages = $this->getConn()->executeQuery(
            "SELECT rtp.*, rtp.ports as ports_prev, pup.ports as ports_actual
            FROM reseller_tracked_packages rtp
            INNER JOIN proxy_user_packages pup ON pup.id = rtp.package_id
            WHERE rtp.date_charged_until <= ?",
            [date('Y-m-d H:i:s', time() - $this->getSetting('chargeDelay'))])
            ->fetchAll();
        foreach ($packages as $package) {
            $totalPorts = $this->getTotalPorts($package[ 'reseller_id' ], $package['country'], $package['category']);
            $priceData = $this->calculatePrice(
                $package[ 'reseller_id' ],
                $package[ 'country' ],
                $package[ 'category' ],
                $totalPorts,
                strtotime($package['date_charged_until'])
            );

            if (!$priceData) {
                $this->log("Price is not calculated for reseller's {$package['reseller_id']} package " .
                    "{$package['country']}-{$package['category']} x {$package['ports_actual']} on recurring", $package, [
                    'resellerId' => $package[ 'reseller_id' ],
                    'userId'     => $package[ 'user_id' ]
                ]);
                continue;
            }

            $total = $priceData['price'] * $package['ports_actual'];

            $this->log("Recurring reseller's {$package['reseller_id']} package " .
                "{$package['country']}-{$package['category']} x {$package['ports_actual']}",
                array_merge($package, [
                    'price'              => $priceData[ 'price' ],
                    'totalResellerPorts' => $totalPorts,
                    'total'              => $total,
                    'totalPorts'         => $totalPorts,
                    'balance'            => $this->getBalance($package[ 'reseller_id' ]) - $total,
                    'dueDate'            => date('Y-m-d H:i:s', $priceData[ 'dueDate' ]),
                    'formula'            => "({$priceData['formula']}) x {$package['ports_actual']}"
                ]), [
                    'resellerId' => $package[ 'reseller_id' ],
                    'userId'     => $package[ 'user_id' ]
                ]);

            $this->getConn()->update('reseller_tracked_packages',
                [
                    'ports'              => $package[ 'ports_actual' ],
                    'date_charged'       => $package[ 'date_charged_until' ],
                    'date_charged_until' => date('Y-m-d H:i:s', $priceData[ 'dueDate' ])
                ], ['id' => $package['id']]);
            $this->updateBalance($package[ 'reseller_id' ], -$total);
        }

        return true;
    }

    protected function calculatePrice($resellerId, $country, $category, $totalPorts, $dueDate = false)
    {
        static $pricing;

        if (!$pricing) {
            $pricing = new PricingHelper($this->app);
        }

        $price = $pricing->getResellerPricing($country, $category, $totalPorts, $resellerId);
        $chargeCycleSec = 60 * 60 * 24 * $this->getSetting('chargeCycle');

        if (!$price) {
            return false;
        }

        // Default values
        $newDueDate = time() + $chargeCycleSec;
        $formula = "$price";
        $price = $price / 30 * $this->getSetting('chargeCycle');

        // Target: unknown
        if (!$dueDate) {
            // Default values
        }
        else {
            // In future
            // Target: upgrade/downgrade
            if ($dueDate >= time()) {
                $days = round((($dueDate - time()) / (60 * 60 * 24)));
                $newDueDate = $days ? $dueDate : $dueDate + $chargeCycleSec;
                $formula = "$price" .
                    (($days and $days != $this->getSetting('chargeCycle')) ?
                        " / 30 x $days" : '');
                if ($days and $days != $this->getSetting('chargeCycle')) {
                    $price = $price / 30 * $days;
                }
            }
            // In the past
            else {
                // In cycle, just shift the next due date for one cycle
                // Target: recurring or new
                if ($dueDate > (time() - $chargeCycleSec)) {
                    $newDueDate = $dueDate + $chargeCycleSec;
                }
                // Out of cycle, means record is very outdated, i.e. the first cron run
                // Target: initial cron run
                else {
                    // Default values
                }
            }
        }

        return ['price' => round($price, 4), 'dueDate' => $newDueDate, 'formula' => $formula];
    }

    protected function getBalance($resellerId)
    {
        return $this->getConn()->executeQuery("SELECT credits FROM resellers WHERE id = ?", [$resellerId])->fetchColumn();
    }

    protected function updateBalance($resellerId, $diff)
    {
        $sign = $diff > 0 ? '+' : '-';

        $this->getConn()->executeQuery("UPDATE resellers SET credits = credits $sign ? WHERE id = ?",
            [abs($diff), $resellerId]);
    }

    protected function getTotalPorts($resellerId, $country, $category, $onlyCharged = true)
    {
        $parameters = [
            'country' => $country,
            'category' => Port::toOldCategory($category),
            'resellerId' => $resellerId
        ];

        return $onlyCharged ? $this->getConn()->executeQuery(
            "SELECT SUM(ports) 
            FROM reseller_tracked_packages 
            WHERE ports > 0 AND reseller_id = :resellerId AND country = :country AND category = :category", $parameters)
            ->fetchColumn() :
            $this->getConn()->executeQuery(
            "SELECT SUM(pup.ports) 
                FROM proxy_user_packages pup
                INNER JOIN proxy_users pu ON pu.id = pup.user_id
                WHERE pup.ports > 0
                AND pu.reseller_id = :resellerId AND pup.country = :country AND pup.category = :category", $parameters)
            ->fetchColumn();
    }

    protected function plus($n1, $n2)
    {
        return ($n1 * 1000 + $n2 * 1000) / 1000;
    }
}
