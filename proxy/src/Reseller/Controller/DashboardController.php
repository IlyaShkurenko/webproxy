<?php

namespace Reseller\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

class DashboardController
{
    /**
     * @var \Silex\Application
     */
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }
    
    public function indexAction()
    {        
        $user = $this->app['app.user_management']->getUser();
        if (!$user) {
            return new RedirectResponse( $this->app['url_generator']->generate('login') );
        }
        
        $charges = $this->app['dbs']['proxy']->fetchAssoc("
            SELECT sum( (price * count) ) as charges
            FROM (
                SELECT country, category, sum(count) as count
                FROM reseller_users ru
                JOIN reseller_user_packages rup ON ru.id = rup.reseller_user_id    
                WHERE reseller_id = ? and count > 0
                GROUP BY country, category
            ) as rup
            LEFT JOIN reseller_pricing rp ON rup.country = rp.country AND rup.category = rp.category AND count >= min AND count <= max",
            [ $user['id'] ]
        );
        
        return $this->app['twig']->render('Dashboard/main.twig', array(
            'api_key' => $user['api_key'],
            'charges' => $charges['charges'],
            'chargesDaily' => $charges['charges'] / 30,
            'credits' => $user['credits']
        ));
    }
    
    public function addCredits($amount)
    {
        $user = $this->app['app.user_management']->getUser();
        if (!$user) {
            return new RedirectResponse( $this->app['url_generator']->generate('login') );
        }

        $user = $this->app['app.user_management']->getUser();        
        $amount = floatval($amount);
        if ($amount > 0 and $this->app[ 'app.am_management' ]) {
            return $this->app->redirect($this->app[ 'app.am_management' ]->getAddCreditsUrl($user['email'], $amount));
        }
        return $this->app->redirect("/");
    }
    
    public function loginAction()
    {
        return $this->app['twig']->render('Dashboard/login.twig', array(
            'name' => 'name1',
        ));
    }
}