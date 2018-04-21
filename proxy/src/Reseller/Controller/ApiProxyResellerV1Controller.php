<?php

namespace Reseller\Controller;

use Proxy\Assignment\Port\IPv4\OldResellerPort;
use Reseller\Helper\PricingHelper;

class ApiProxyResellerV1Controller extends AbstractAPIController
{
    
    public function balanceAction()
    {
        $reseller = $this->getReseller();

        $this->userLog(null, $reseller['credits']);
        
        return [
            'reseller_id' => $reseller['id'],
            'email' => $reseller['email'],
            'credits' => $reseller['credits'],
        ];
    }

    public function pricingAction()
    {
        $reseller = $this->getReseller();

        $this->userLog(null);

        return [
            'reseller_id' => $reseller[ 'id' ],
            'pricing'     => call_user_func(function ($tiers) {
                $return = [];

                // Data map
                foreach ($tiers as $category => $categoryData) {
                    foreach ($categoryData as $country => $data) {
                        $return[ $country ][ OldResellerPort::toOldCategory($category) ] = $data;
                    }
                }

                return $return;
            }, (new PricingHelper($this->app))->getAllResellerPricing($reseller[ 'id' ]))
        ];
    }
}