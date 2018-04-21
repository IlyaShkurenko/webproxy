<?php

namespace Common\Crons;

use Application\Crons\AbstractCron;
use Application\Helper;

class UserGenerateLoginCron extends AbstractCron
{
    protected $config = ['enabled' => true];

    public function run()
    {
        $sql = "SELECT u.id, u.email, u.reseller_id
                FROM `proxy_users` u
                WHERE login IS NULL";
        $stmt = $this->getConn()->executeQuery($sql);
        while ($row = $stmt->fetch()) {
            $this->debug("Generating login for \"{$row['email']}\"", [], ['userId' => $row['id']]);

            $generatedLogin = Helper::generateLogin($row['email'], $row['reseller_id']);
            $this->getConn()->update('proxy_users', ['login' => $generatedLogin], ['id' => $row['id']]);
        }
        
        return true;
    }
}
