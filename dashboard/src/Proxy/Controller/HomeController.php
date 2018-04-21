<?php

namespace Proxy\Controller;

class HomeController extends AbstractController
{
    public function index() {
        if ($this->getUser()->isAuthorized()) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->app['twig']->render('home/index.html.twig');
    }
}
