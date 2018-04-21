<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Events\AbstractHookListener;

class LoginToProxyDashboardLink extends AbstractHookListener
{
    protected $name = 'ClientAreaSecondarySidebar';

    protected function execute($homePagePanels = null)
    {
        // Should be at least one proxy
        if (!UserService::findManyByUser($this->getUserId())) {
            return;
        }

        $panel = $homePagePanels->getChild('Client Shortcuts');

        if ($panel) {
            $panel->addChild('login-to-proxy-dashboard', [
                'label' => 'Log In to Proxy Dashboard',
                'uri' => 'modules/addons/' . $this->getModule()->getId() . '/redirect.php?' . http_build_query([
                    'url' => rtrim($this->getModule()->getConfig('proxyDashboardUrl'), '/') . '/dashboard/re-login?' .
                        http_build_query(array_merge(!empty($_SESSION['adminid']) ? [
                            'asAdmin' => 1
                        ] : []))]),
                'order' => 0,
                'icon' => 'fa-sign-in fa-fw'
            ]);
        }
    }
}