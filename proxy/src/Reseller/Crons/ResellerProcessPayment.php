<?php 

namespace Reseller\Crons;

class ResellerProcessPayment extends AbstractOldStyleCron
{
    protected $config = [
        'schedule' => '*/5 * * * *',
        'enabled'  => false
    ];

    protected $settings = [
        'amemberItem' => 272,
        'periodHours' => 24
    ];

    public function processPayments()
    {
        $sql = "SELECT au.email, aip.invoice_payment_id, aip.amount, aip.dattm as date 
            FROM banditim_amember.am_invoice_item aii
            JOIN banditim_amember.am_invoice ai ON aii.invoice_id = ai.invoice_id
            JOIN banditim_amember.am_invoice_payment aip ON ai.invoice_id = aip.invoice_id
            JOIN banditim_amember.am_user au ON au.user_id = aip.user_id
            WHERE item_id = :itemId AND DATE_ADD(aip.dattm, INTERVAL :periodHours HOUR) > :nowTime";
        $paymentRecords = $this->getConn()->fetchAll($sql,
            [
                'itemId'      => $this->getSetting('amemberItem'),
                'periodHours' => $this->getSetting('periodHours'),
                'nowTime'     => date('Y-m-d H:i:s')
            ], ['periodHours' => \PDO::PARAM_INT]
        );
        foreach ($paymentRecords as $paymentRecord) {
            $payment = $this->getConn('rs')->fetchAssoc("SELECT * FROM reseller_payments WHERE payment_id = ?", [$paymentRecord['invoice_payment_id']]);
            if (!$payment) {
                $reseller = $this->getConn('rs')->fetchAssoc("SELECT * FROM reseller WHERE email = ?", [$paymentRecord['email']]);
                if ($reseller) {
                    $this->output('Adding reseller payment ' . json_encode([
                        'payment' => [
                            'id'     => $paymentRecord[ 'invoice_payment_id' ],
                            'amount' => $paymentRecord[ 'amount' ],
                            'date'   => $paymentRecord[ 'date' ]
                        ],
                        'user'    => [
                            'id'    => $reseller[ 'id' ],
                            'email' => $reseller[ 'email' ]
                        ],
                        'credits' => [
                            'before' => $reseller['credits'],
                            'after' => $reseller['credits'] + $paymentRecord[ 'amount' ]
                        ]
                    ]));

                    $this->getConn('rs')->insert('reseller_payments', [
                        'payment_id' => $paymentRecord[ 'invoice_payment_id' ],
                        'amount'     => $paymentRecord[ 'amount' ],
                        'user_id'    => $reseller[ 'id' ],
                        'date_added' => $paymentRecord[ 'date' ]
                    ]);

                    $this->getConn('rs')->executeUpdate("UPDATE reseller SET credits = credits + ? WHERE id = ?", [
                        $paymentRecord['amount'],
                        $reseller['id']
                    ]);
                }
                else {
                    $this->output('Incoming payment, but reseller have not found ' . json_encode([
                        'payment' => [
                            'id'     => $paymentRecord[ 'invoice_payment_id' ],
                            'amount' => $paymentRecord[ 'amount' ],
                            'date'   => $payment[ 'date' ]
                        ],
                        'user' => ['email' => $paymentRecord['email']]
                    ]));
                }
            }
        }
    }

    public function run()
    {
        $this->processPayments();

        return true;
    }
}