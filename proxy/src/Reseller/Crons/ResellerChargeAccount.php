<?php 

namespace Reseller\Crons;

use Reseller\Helper\PricingHelper;

class ResellerChargeAccount extends AbstractOldStyleCron
{
    protected $config = [
        'schedule' => '*/15 * * * *',
        'enabled'  => false
    ];

    public function chargeAccounts()
    {
        $userBillingCycle = 30;
        $resellerChargeCycle = 30;
        $timeAhead = 12 * 60 * 60; // to ensure paypal payment is received by reseller

        $currentDate = time();

        $sql = "SELECT up.*, rs.reseller_id
            FROM user_ports up
            JOIN reseller_users rs ON up.user_id = rs.id
            WHERE user_type = 'RS' and (rs_charge_date <= ? or rs_charge_date is null) ORDER BY id";
        $portsToCharge = $this->app['dbs']['proxy']->fetchAll($sql, [date('Y-m-d H:i:s', strtotime("-$timeAhead sec", $currentDate))]);
        $pricing = new PricingHelper($this->app);
        
        foreach($portsToCharge as $accountToCharge) {
        
            $totalCount = $this->app['dbs']['proxy']->fetchAssoc(
                "SELECT sum(count) as total
                FROM reseller_user_packages rup
                JOIN reseller_users ru ON rup.reseller_user_id = ru.id
                WHERE ru.reseller_id = ? and rup.category = ?",
                [$accountToCharge['reseller_id'], $accountToCharge['category']]);

            $pricingValue = $pricing->getResellerPricing(
                $accountToCharge[ 'country' ],
                $accountToCharge[ 'category' ],
                $totalCount[ 'total' ],
                $accountToCharge[ 'reseller_id' ]
            );

            // Determine billing (reseller's customer) and charge (reseller) date
            $firstBillingDate = date('Y-m-d H:i:s', strtotime($accountToCharge['time_assigned']));
            $firstBillingDaysAgo = ($currentDate - strtotime($accountToCharge['time_assigned'])) / (24 * 60 * 60);
            $billedTimes = floor(round($firstBillingDaysAgo) / $resellerChargeCycle);
            $billingToday = false;
            do {
                $loop = false;

                $nextBillingDate = strtotime(sprintf('+%s days', ($billedTimes + 1) * $resellerChargeCycle), strtotime($firstBillingDate));
                $nextBillingDaysLeft = ($nextBillingDate - $currentDate) / (24 * 60 * 60);

                // Today is due billing date, count next billing as today billing
                if (
                    $billingDiff =
                        ($currentDate
                            - $nextBillingDate
                            + $resellerChargeCycle * 24 * 60 * 60
                            - $timeAhead
                        ) / (24 * 60 * 60) and
                    !round($billingDiff)
                ) {
                    $billedTimes--;
                    $billingToday = true;
                    $loop = true;
                }
                else {
                    $nextBillingDate = date('Y-m-d H:i:s', $nextBillingDate);
                }
            }
            while ($loop);

            $prevBillingDate = date('Y-m-d H:i:s', strtotime(sprintf('+%s days', $billedTimes * $resellerChargeCycle), strtotime($firstBillingDate)));
            $prevBillingDaysAgo = ($currentDate - strtotime($prevBillingDate)) / (24 * 60 * 60);

            // Determine charge cycle, as by default it's 30 days (as configured),
            // but for those customers who still charged daily rules should be different,
            // they should be charged for price for those days that left to next user billing date
            $chargeCycle = $resellerChargeCycle;
            if (!empty($accountToCharge['rs_charge_date']) and !$billingToday) {
                $chargeCycle = round($nextBillingDaysLeft);
            }

            if ($userBillingCycle == $chargeCycle) {
                $toCharge = $pricingValue;
            }
            else {
                // idk why * 1000 / 1000 and ceil
                // seems unreasonable, while round() can be used, but ok leave it as it is
                $toCharge = ceil( ($pricingValue / $userBillingCycle * $chargeCycle) * 1000) / 1000;
            }

            $this->app['db']->insert('reseller_charges', [
                'reseller_id' => $accountToCharge['reseller_id'],
                'amount' => $toCharge,
                'message' => json_encode([
                    'portId'              => $accountToCharge[ 'id' ],
                    'now'                 => date('Y-m-d H:i:s', $currentDate),
                    'userId'              => $accountToCharge[ 'user_id' ],
                    'chargeCycle'         => $chargeCycle,
                    'pricingValue'        => $pricingValue,
                    'toCharge'            => $toCharge,
                    'firstBillingDaysAgo' => $firstBillingDaysAgo,
                    'firstBilling'        => $firstBillingDate,
                    'prevBillingDaysAgo'  => $prevBillingDaysAgo,
                    'prevBilling'         => $prevBillingDate,
                    'nextBillingDaysLeft' => $nextBillingDaysLeft,
                    'nextBilling'         => $nextBillingDate,
                    'billingToday'        => $billingToday,
                    'creditsBefore'       => $this->app[ 'db' ]->fetchColumn(
                        'SELECT credits FROM reseller WHERE id = ?', [$accountToCharge[ 'reseller_id' ]]),
                    'rsChargeDate'        => $accountToCharge[ 'rs_charge_date' ],
                    'timeAhead'           => $timeAhead / (60 * 60),
                    'country'             => $accountToCharge[ 'country' ],
                    'category'            => $accountToCharge[ 'category' ]
                ], JSON_PRETTY_PRINT)
            ]);

            echo sprintf('Proxies for user %s (rs - %s), port - %s, charge cycle - %s, to charge - %s',
                $accountToCharge['user_id'], $accountToCharge['reseller_id'], $accountToCharge['id'], $chargeCycle,
                    $toCharge) . PHP_EOL;

            $this->app['db']->executeUpdate('UPDATE reseller SET credits = credits - ? WHERE id = ?',
                [$toCharge, $accountToCharge['reseller_id']], [\PDO::PARAM_INT]);

            $this->app['dbs']['proxy']->executeUpdate(
                "UPDATE user_ports SET rs_charge_date = ? WHERE id = ?",
                [
                    // if nextBillingDate is today, just rewind that date
                    $billingToday ?
                        date('Y-m-d H:i:s', strtotime(sprintf('+%s days', $resellerChargeCycle), strtotime($nextBillingDate))) :
                        $nextBillingDate,
                    $accountToCharge[ 'id' ]
                ]
            );

        }

        return true;
    }
    
    public function sendEmails()
    {
        $sql = "SELECT * FROM blazing_reseller.reseller WHERE credits < 0";
        $resellers = $this->app['dbs']['proxy']->fetchAll($sql);
        
        foreach ($resellers as $reseller) {
            $message = "Your Reseller Account balance is currently at " . $reseller['credits'] . ". If you do not add credits to your balance within 24 hours we will deactivate the proxies for your users. You can add credits at http://www.blazingseollc.com/reseller/login";
            mail("michael@splicertech.com", 'Negative Balance on your reseller account', $message, "From: admin@blazingseollc.com");            
            //mail($reseller['email'], 'Negative Balance on your reseller account', $message, "From: admin@blazingseollc.com");
        }
    }

    public function run()
    {
        $this->chargeAccounts();

        return true;
    }
}