<?php

namespace Proxy\Crons;

use Application\Helper;
use Exception;

class ProxyCheckConnectActiveCron extends AbstractProxyCheckConnect
{

    protected $config = [
        'enabled' => false
    ];

    protected $settings = [
        'dryRun'    => false,
        'queueSize' => 10000,
        'emailDead' => false,
        'check' => [
            'timeout' => [
                'load' => 15
            ]
        ],
        'ignore' => [
            // Like source, can contain % as in LIKE query
            'source' => []
        ]
    ];

    public function run()
    {
        $client = $this->buildHttpClient();

        $qb = $this->getConn()->createQueryBuilder();
        $qb->select('id', 'ip', 'port', 'source', 'dead_count')
            ->from('proxies_ipv4')
            ->orderBy('last_check')
            ->where($qb->expr()->andX(
                $qb->expr()->orX(
                    $qb->expr()->eq('active', 1),
                    $qb->expr()->eq('pcheck', 1)
                ),
                $qb->expr()->eq('dead_count', 0),
                $qb->expr()->eq('dead', 0)
            ));

        // Ignore sources
        if ($this->getSetting('ignore.source')) {
            foreach ($this->getSetting('ignore.source') as $ignore) {
                $qb->andWhere($qb->expr()->notLike('source', $qb->createNamedParameter($ignore)));
            }
        }

        Helper::queueStatement($qb->execute(), $this->getSetting('queueSize'),
            function ($rows, $countProcessed, $countAll) use ($client) {
                $this->checkProxyByRows($client, $rows, $countProcessed, $countAll,
                    function (Exception $e = null, $row) {
                        if (!$this->getSetting('dryRun')) {
                            $update = [
                                'last_check' => date('Y-m-d H:i:s')
                            ];

                            if ($e) {
                                $update[ 'last_error' ] = $e->getMessage();
                                $update[ 'dead_count' ] = 1;

                                // Send email?
                                if ($this->getSetting('emailDead')) {
                                    $this->emailQueueDead($row, $e->getMessage());
                                }
                            }

                            $this->getConn()->update('proxies_ipv4', $update, ['id' => $row[ 'id' ]]);
                        }
                    });

            });

        $this->emailQueuePush();

        return true;
    }
}
