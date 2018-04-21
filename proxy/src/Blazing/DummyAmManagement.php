<?php

namespace Blazing;

use Symfony\Component\HttpFoundation\Session\Session;

class DummyAmManagement extends AmManagement
{
    /**
     * @var \Silex\Application
     */
    private $app;

    private $session = [];

    public function __construct($app)
    {
        $this->app = $app;
        $this->session = new Session();
    }

    public function loggedIn()
    {
        return !!$this->getEmail();
    }

    public function getEmail()
    {
        return $this->session->get('email');
    }

    public function getLoginURL()
    {
        return $this->app['url_generator']->generate('dam_login');
    }

    public function getLogoutURL()
    {
        return $this->app['url_generator']->generate('dam_logout');
    }

    public function getAddCreditsUrl($email, $amount) {
        return $this->app['url_generator']->generate('dam_charge', ['amount' => $amount]);
    }

    public function hasAccess()
    {
        return $this->loggedIn();
    }
}
