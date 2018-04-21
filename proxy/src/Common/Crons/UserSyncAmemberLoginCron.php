<?php

namespace Common\Crons;

use Application\Crons\AbstractCron;
use Application\Helper;

class UserSyncAmemberLoginCron extends AbstractCron
{
    protected $config = ['enabled' => false];

    public function run()
    {
        $sql = "SELECT u.id, u.email, au.user_id as amember_id, au.login as login_amember, u.login as login, u.reseller_id
                FROM `proxy_users` u
                INNER JOIN banditim_amember.am_user au ON au.user_id = u.amember_id
                WHERE u.amember_id IS NOT NULL AND u.reseller_id = 1
                  AND (u.login IS NULL OR u.login NOT LIKE CONCAT('%', REPLACE(au.login, '_', '\_'), '%'))";
        $stmt = $this->getConn()->executeQuery($sql);
        while ($row = $stmt->fetch()) {
            $generatedLogin = Helper::generateLogin($row['email'], $row['reseller_id']);
            $this->log(
                "Tieing user \"{$row['email']}\"",
                ['amemberId' => $row['amember_id'], 'previousLogin' => $row['login'], 'login' => $row['login_amember']],
                ['userId' => $row['id']]
            );

            $this->getConn()->update('proxy_users', [
                'login' => join(',', array_unique([
                        // Preserve generated logins
                        $generatedLogin,
                        // Save imported logins (to sync properly)
                        $row['login_amember'],
                        // Sanitize logins
                        Helper::sanitizeLogin($row['login_amember'], $this->getApp())
                    ])
                )
            ], ['id' => $row['id']]);
        }

        return true;
    }
}
