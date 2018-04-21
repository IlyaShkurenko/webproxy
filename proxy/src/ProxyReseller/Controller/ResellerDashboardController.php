<?php

namespace ProxyReseller\Controller;

use Application\AbstractController;
use Proxy\Assignment\Port\IPv4\Port;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ResellerDashboardController extends AbstractController
{
    public function indexAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return new RedirectResponse($this->app['url_generator']->generate('reseller.login'));
        }

        $charges = $this->getConn()->fetchAssoc("
            SELECT sum( (price * count) ) as charges
            FROM (
                SELECT country, category, sum(ports) as count
                FROM proxy_users pu
                INNER JOIN proxy_user_packages pup ON pu.id = pup.user_id AND pup.ip_v = :ipv4
                WHERE pu.reseller_id = :resellerUserId and ports > 0
                GROUP BY country, category
            ) as pup
            LEFT JOIN reseller_pricing rp ON pup.country = rp.country AND pup.category = rp.category AND count >= min AND count <= max",
            [ 'resellerUserId' => $user['id'], 'ipv4' => Port::INTERNET_PROTOCOL ]
        );

        return $this->app['twig']->render('Dashboard/main.twig', [
            'api_key' => $user['api_key'],
            'charges' => $charges['charges'],
            'chargesDaily' => $charges['charges'] / 30,
            'credits' => $user['credits']
        ]);
    }

    public function addCredits($amount)
    {
        $user = $this->getUser();
        if (!$user) {
            return new RedirectResponse( $this->app['url_generator']->generate('reseller.login') );
        }

        $amount = floatval($amount);
        if ($amount > 0) {
            return new RedirectResponse($this->app[ 'app.am_management' ]->getAddCreditsUrl($user['email'], $amount));
        }

        return new RedirectResponse($this->app['url_generator']->generate('reseller.dashboard'));
    }

    public function loginAction()
    {
        $error = '';
        if (isset($this->app['app.am_management'])) {
            if ($this->app['app.am_management']->getEmail()) {
                if ($this->app['app.am_management']->hasAccess()) {
                    return new RedirectResponse( $this->app['url_generator']->generate('reseller.dashboard') );
                }
                else {
                    $error = 'You should buy subscription before!';
                }
            }
        }

        return $this->app['twig']->render('Dashboard/login.twig', ['error' => $error]);
    }

    // Helpers

    protected function getUser()
    {
        if (isset($this->app['app.am_management'])) {
            $email = $this->app['app.am_management']->getEmail();
            $access = $this->app['app.am_management']->hasAccess();
        }

        if (empty($email) or empty($access)) {
            return false;
        }

        $user = $this->getConn()->fetchAssoc('SELECT * FROM resellers WHERE email = ?', [$email]);
        if (!$user) {
            $key = preg_replace('~[@\.\+].+~', '', $email) . md5($email . date('Y-m-d H:i:s'));
            $this->getConn()->insert('resellers', [
                'email' => $email,
                'api_key' => $key,
                'credits' => 0,
            ]);
            $id = $this->getConn()->lastInsertId();
            $user = $this->getConn()->fetchAssoc('SELECT * FROM resellers WHERE id = ?', [$id]);
        }

        return $user;
    }
}
