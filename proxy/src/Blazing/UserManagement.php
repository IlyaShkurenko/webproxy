<?php

namespace Blazing;

class UserManagement
{
    private $app;

    public function __construct($app) {
        $this->app = $app;
    }

    public function getUser() {

        if (!empty($this->app['app.am_management'])) {
            $email = $this->app['app.am_management']->getEmail();
            $access = $this->app['app.am_management']->hasAccess();
        }

        if (empty($email) || empty($access)) {
            return false;
        }
        
        $user = $this->app['db']->fetchAssoc('SELECT * FROM reseller WHERE email = ?', [$email]);
        if (!$user) {
            $key = md5($email . date('Y-m-d H:i:s'));
            $this->app['db']->insert('reseller', [
                'email' => $email,
                'api_key' => $key,
                'credits' => 0,
            ]);
            $id = $this->app['db']->lastInsertId();
            return ['id' => $id, 'email' => $email, 'api_key' => $key];
        }
        return $user;
    }
    
    public function getReseller($api_key){
        $user = $this->app['db']->fetchAssoc('SELECT * FROM reseller WHERE api_key = ?', [$api_key]);
        return $user;
    }
    
    public function writeLog($request, $error, $reseller_id = null, $additional = null) {
        if (is_array($additional)) {
            $additional = json_encode($additional);
        }
        $this->app['db']->insert('reseller_log', [
            'request' => $request,
            'error' => $error,
            'reseller_id' => $reseller_id, 
            'additional' => $additional
        ]);
    }
}