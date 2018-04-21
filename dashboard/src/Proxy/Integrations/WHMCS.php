<?php

namespace Proxy\Integrations;

use Proxy\Integration;
use Silex\Application;

class WHMCS extends Integration {

    public function getClientDetailsByEmail($email) {
        $post = [
            'action' => 'getclientsdetails',
            'email' => $email,
            'stats' => false
        ];
        return $this->getRequestHandler()->doRequest($post);
    }

    public function getClientProducts($clientId) {
        $post = [
            'action' => 'getclientsproducts',
            'clientid' => $clientId,
        ];
        return $this->getRequestHandler()->doRequest($post);
    }

    public function oAuthToken($code, $redirectUrl) {
        return $this->doCurlJson(
            $this->app['config.whmcs.path'] . "oauth/token.php",
            [
                "code" => $code,
                "client_id" => $this->app['config.whmcs.client'],
                "client_secret" => $this->app['config.whmcs.secret'],
                "redirect_uri" => $redirectUrl,
                "grant_type" => "authorization_code"
            ]
        );
    }

    public function oAuthUserInfo($access_token) {
        return $this->doCurlJson(
            $this->app['config.whmcs.path'] . "oauth/userinfo.php",
            [],
            'POST',
            ['Content-Type: application/json' , "Authorization: Bearer $access_token"]
        );
    }

    public function api($method, array $parameters)
    {
        return $this->getRequestHandler()->doRequest(array_merge($parameters, ['action' => $method]));
    }

    /**
     * @return WHMCSRequestHandler
     */
    protected function getRequestHandler()
    {
        return $this->app[ 'integration.whmcs.api.request_handler' ];
    }
}