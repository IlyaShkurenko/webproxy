<?php

namespace Reseller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class DummyAmController
{
    /**
     * @var \Silex\Application
     */
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function loginAction(Request $request)
    {
        $email = $request->get('amember_login');
        $redirect = $request->get('amember_redirect_url', $this->app['url_generator']->generate('dashboard'));

        if ($email) {
            (new Session())->set('email', $email);

            return $this->app->redirect($redirect);
        }

        return new Response('No email passed', 400);
    }

    public function logoutAction()
    {
        (new Session())->remove('email');

        return $this->app->redirect($this->app['url_generator']->generate('dashboard'));
    }

    public function chargeAction(Request $request)
    {
        // Not authorized
        if (! $email = (new Session())->get('email')) {
            return $this->app->redirect($this->app['url_generator']->generate('login'));
        }

        $amount = $request->get('amount');

        $this->app['dbs']['reseller']->executeQuery("
            UPDATE reseller SET credits = credits + ? WHERE email = ?
        ", [$amount, $email], [\PDO::PARAM_INT]);

        return $this->app->redirect($this->app['url_generator']->generate('dashboard'));
    }
}
