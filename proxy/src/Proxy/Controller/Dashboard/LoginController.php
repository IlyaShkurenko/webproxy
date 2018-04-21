<?php

namespace Proxy\Controller\Dashboard;

use ErrorException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LoginController extends AbstractController
{
    public function login()
    {
        if (function_exists('mcrypt_create_iv')) {
            $this->app['session']->set('oauth_token', bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM)));
        } else {
            $this->app['session']->set('oauth_token', bin2hex(openssl_random_pseudo_bytes(32)));
        }

        if ($this->request->get('redirect')) {
            $this->app['session']->set('oauth_redirect', $this->request->get('redirect'));
        }

        $url = WHMCS_PATH . "/oauth/authorize.php?client_id=" . WHMCS_CLIENT .
            "&response_type=code" .
            "&scope=openid%20profile%20email" .
            "&redirect_uri=" . $this->getUrl('proxy_dashboard_whmcs_callback') .
            "&state=security_token%3D" . $this->app['session']->get('oauth_token') . "%26url%3D" . $this->getUrl('proxy_dashboard_index');

        return new RedirectResponse($url);
    }

    public function code() {
        $code = $this->request->get('code');

        try {
            if (hash_equals($code, $this->app[ 'session' ]->get('oauth_token'))) {
                throw new \Exception('Bad Auth Token');
            }

            $redirectUri = $this->getUrl('proxy_dashboard_whmcs_callback');

            $info = $this->app[ 'integration.whmcs.api' ]->oAuthToken($code, $redirectUri);
            if (!empty($info[ 'error' ])) {
                throw new ErrorException('Authentication error: ' . json_encode($info));
            }

            $access_token = $info[ 'access_token' ];

            $userInfo = $this->app[ 'integration.whmcs.api' ]->oAuthUserInfo($access_token);
            if (!empty($userInfo[ 'error' ])) {
                throw new ErrorException('Authentication error: ' . json_encode($userInfo));
            }

            $details = $this->app[ 'integration.whmcs.api' ]->getClientDetailsByEmail($userInfo[ 'email' ]);

            if (empty($details[ 'userid' ])) {
                if (!empty($details[ 'result' ]) and 'error' == $details[ 'result' ] and !empty($details[ 'message' ])) {
                    throw new ErrorException('Authentication error: ' . $details[ 'message' ]);
                }
                else {
                    throw new ErrorException('Authentication error! Please try later or contact with us');
                }
            }
            $whmcs_id = $details[ 'userid' ];

            $user = $this->getConn()->fetchAssoc("SELECT * FROM proxy_users WHERE whmcs_id = ?", [$whmcs_id]);

            if (empty($user[ 'admin' ])) {
                throw new ErrorException("User \"{$user['email']}\" has no rights to access the page");
            }

            $this->app[ 'session' ]->set('user', $user);

            if ($this->app[ 'session' ]->has('oauth_redirect')) {
                $redirectTo = $this->app[ 'session' ]->get('oauth_redirect');
                $this->app[ 'session' ]->remove('oauth_redirect');
            }
            else {
                $redirectTo = $this->getUrl('proxy_dashboard_index');
            }
        }
        catch (ErrorException $e) {
            return $this->addFlashError($e->getMessage())->redirectToRoute('proxy_dashboard_index');
        }

        return new RedirectResponse($redirectTo);
    }

    public function logout() {
        $this->app['session']->remove('user');

        return $this->redirectToRoute('proxy_dashboard_index');
    }
}
