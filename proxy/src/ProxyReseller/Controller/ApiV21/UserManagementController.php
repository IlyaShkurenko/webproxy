<?php

namespace ProxyReseller\Controller\ApiV21;

use ProxyReseller\Controller\ApiV20\UserManagementController as BaseController;

class UserManagementController extends BaseController
{
    protected function getUserDetails($userId)
    {
        $controller = new UserController($this->app);
        $controller->setRequest($this->request);

        return $controller->getDetailsAction($userId);
    }
}