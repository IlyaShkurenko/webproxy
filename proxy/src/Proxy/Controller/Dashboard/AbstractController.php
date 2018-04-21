<?php

namespace Proxy\Controller\Dashboard;

use Application\AbstractApiController;
use Application\Helper;
use Axelarge\ArrayTools\Arr;
use ErrorException;

class AbstractController extends AbstractApiController
{
    protected $logPath = 'admin-dashboard.log';

    /**
     * Check if user has access
     *
     * @param callable $controller
     * @throws ErrorException
     */
    public function onControllerRequest(callable $controller)
    {

        $exclusions = [
            DashboardController::class => ['indexAction'],
            LoginController::class => ['login', 'logout', 'code']
        ];

        if (!$this->getUser('admin')) {
            // Case is not excluded
            if (
                empty($exclusions[get_class($controller[0])]) or
                ($actions = $exclusions[get_class($controller[0])] and !in_array($controller[1], $actions))
            ) {
                throw new ErrorException(
                    $this->getUser('email') ?
                        ('User "' . $this->getUser('email') . '" has no rights to access the page') :
                        ('Access is prohibited')
                );
            }
        }
    }

    // Shorthands

    /**
     * Get user data
     *
     * @param null $key
     * @param null $default
     * @return array
     */
    protected function getUser($key = null, $default = null)
    {
        $user = $this->app['session']->get('user');

        return !$key ? $user : Arr::getOrElse((array) $user, $key, $default);
    }

    protected function addFlashSuccess($text)
    {
        $this->app['session']->getFlashBag()->add('success', $text);

        return $this;
    }

    protected function addFlashError($text)
    {
        $this->app['session']->getFlashBag()->add('error', $text);

        if ($this->logger) {
            $this->logger->warn($text);
        }

        return $this;
    }

    protected function saveVariableToFlash($key, $value)
    {
        $this->app['session']->getBag('vars')->set($key, $value);

        return $this;
    }

    protected function getVariableFromFlash($key)
    {
        return $this->app['session']->getBag('vars')->get($key);
    }

    protected function renderTemplate($template, array $variables = [])
    {
        return $this->app['twig']->render($template, $variables);
    }

    protected function renderDefaultTemplate(array $variables = [])
    {
        if (empty($this->controller[0]) or !is_object($this->controller[0])) {
            throw new ErrorException('Cannot render default template');
        }

        $dir = 'ProxyDashboard/' . strtolower(str_replace('Controller', '', Helper::getClassBasename($this->controller[0])));
        $template = strtolower(preg_replace('~([A-Z][A-Z]*?[a-z0-9]+)~', '_$1', str_replace('Action', '', $this->controller[1])));
        $ext = 'html.twig';

        return $this->renderTemplate("$dir/$template.$ext", $variables);
    }
}
