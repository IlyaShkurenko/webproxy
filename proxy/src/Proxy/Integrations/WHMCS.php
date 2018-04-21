<?php

namespace Proxy\Integrations;

use Silex\Application;

class WHMCS extends AbstractIntegration
{

    protected $whmcsPath;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->whmcsPath = rtrim($app[ 'config.integration.whmcs.path' ], '/');
    }

    public function getClientDetailsByEmail($email)
    {
        return $this->api('getclientsdetails', [
            'email' => $email,
            'stats' => false
        ]);
    }

    public function oAuthToken($code, $redirectUrl)
    {
        return $this->doCurlJson(
            $this->whmcsPath . "/oauth/token.php",
            [
                "code"          => $code,
                "client_id"     => $this->app[ 'config.integration.whmcs.client' ],
                "client_secret" => $this->app[ 'config.integration.whmcs.secret' ],
                "redirect_uri"  => $redirectUrl,
                "grant_type"    => "authorization_code"
            ]
        );
    }

    public function oAuthUserInfo($access_token)
    {
        return $this->doCurlJson(
            $this->whmcsPath . "/oauth/userinfo.php",
            [],
            'POST',
            ['Content-Type: application/json', "Authorization: Bearer $access_token"]
        );
    }

    public function api($method, array $parameters)
    {
        return $this->doRequest(array_merge($parameters, ['action' => $method]));
    }

    private function doRequest($parameters)
    {
        return $this->doCurlJson(
            $this->whmcsPath . '/includes/api.php',
            array_merge([
                'username'     => $this->app[ 'config.integration.whmcs.username' ],
                'password'     => md5($this->app[ 'config.integration.whmcs.password' ]),
                'responsetype' => 'json',
            ], $parameters)
        );
    }
}
