<?php

namespace Common\Crons;

use Application\Crons\AbstractCron;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class UserSyncAmemberIdCron extends AbstractCron
{

    protected $config = ['enabled' => false];

    public function run()
    {
        $sql = "SELECT u.id, u.email, u.whmcs_id, au.user_id as amember_id
                FROM `proxy_users` u
                INNER JOIN banditim_amember.am_user au ON au.email = u.email
                WHERE u.amember_id IS NULL AND u.reseller_id = 1";
        $stmt = $this->getConn()->executeQuery($sql);
        while ($row = $stmt->fetch()) {
            $this->output(sprintf('Tieing user "%s" (id - "%s", amember id - "%s", whmcs_id - "%s")',
                $row['email'], $row['id'], $row['amember_id'], $row['whmcs_id']));
            try {
                $this->getConn()->update('proxy_users', ['amember_id' => $row['amember_id']], ['id' => $row['id']]);
            }
            catch (UniqueConstraintViolationException $e) {
                // Non blocking catch
                $this->output(sprintf('Non-unique customer email "%s"', $row['email']));
            }
        }

        return true;
    }
}
