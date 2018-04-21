<?php

namespace ProxyReseller\Crons;

class ProxyResellerFetchPayments extends AbstractCron
{
    protected $config = [
        'schedule' => '* * * * *',
        'enabled'  => false
    ];

    protected $settings = [
        'amemberItem' => 272,
        'periodHours' => 24
    ];

    public function run()
    {
        $sql = "SELECT au.email, aip.invoice_payment_id, aip.amount, aip.dattm as date 
            FROM am_invoice_item aii
            JOIN am_invoice ai ON aii.invoice_id = ai.invoice_id
            JOIN am_invoice_payment aip ON ai.invoice_id = aip.invoice_id
            JOIN am_user au ON au.user_id = aip.user_id
            WHERE item_id = :itemId AND DATE_ADD(aip.dattm, INTERVAL :periodHours HOUR) > :nowTime";
        $paymentRecords = $this->getConn('am')->fetchAll($sql,
            [
                'itemId'      => $this->getSetting('amemberItem'),
                'periodHours' => $this->getSetting('periodHours'),
                'nowTime'     => date('Y-m-d H:i:s')
            ], ['periodHours' => \PDO::PARAM_INT]
        );
        foreach ($paymentRecords as $paymentRecord) {
            $payment = $this->getConn()->fetchAssoc("SELECT * FROM reseller_payments WHERE payment_id = ?", [$paymentRecord['invoice_payment_id']]);
            if (!$payment) {
                $reseller = $this->getConn()->fetchAssoc("SELECT * FROM resellers WHERE email = ?", [$paymentRecord['email']]);
                if ($reseller) {
                    $this->log("Incoming reseller \"{$reseller['id']}\" payment for \${$paymentRecord[ 'amount' ]}", json_encode([
                            'payment' => [
                                'id'     => $paymentRecord[ 'invoice_payment_id' ],
                                'amount' => $paymentRecord[ 'amount' ],
                                'date'   => $paymentRecord[ 'date' ],
                                'source' => 'amember'
                            ],
                            'reseller'    => [
                                'id'    => $reseller[ 'id' ],
                                'email' => $reseller[ 'email' ]
                            ],
                            'credits' => [
                                'before' => $reseller['credits'],
                                'after' => $reseller['credits'] + $paymentRecord[ 'amount' ]
                            ]
                        ]), ['resellerId' => $reseller['id']]);

                    $this->getConn()->insert('reseller_payments', [
                        'payment_id'  => $paymentRecord[ 'invoice_payment_id' ],
                        'amount'      => $paymentRecord[ 'amount' ],
                        'reseller_id' => $reseller[ 'id' ],
                        'date_added'  => $paymentRecord[ 'date' ]
                    ]);

                    $this->getConn()->executeUpdate("UPDATE resellers SET credits = credits + ? WHERE id = ?", [
                        $paymentRecord['amount'],
                        $reseller['id']
                    ]);
                }
                else {
                    $this->log("Incoming reseller \"{$paymentRecord['email']}\" payment " .
                        "for \${$paymentRecord[ 'amount' ]}, although reseller have not found ", [
                            'payment' => [
                                'id'     => $paymentRecord[ 'invoice_payment_id' ],
                                'amount' => $paymentRecord[ 'amount' ],
                                'date'   => $payment[ 'date' ]
                            ],
                            'user' => ['email' => $paymentRecord['email']]
                        ]);
                }
            }
        }

        return true;
    }
}
