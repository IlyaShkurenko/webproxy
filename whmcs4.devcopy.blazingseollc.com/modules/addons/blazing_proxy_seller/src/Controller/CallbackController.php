<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Controller;

use Symfony\Component\HttpFoundation\Request;

class CallbackController extends AbstractSelfDrivenController
{
    public function getAffiliateAction(Request $request)
    {
        $affiliateId = false;

        // Affiliate id is stored in cookies, look for it
        $cookie = $request->cookies->all();
        foreach ($cookie as $key => $value) {
            if (false !== stripos($key, 'affiliateId')) {
                $affiliateId = $value;
                break;
            }
        }

        return ['id' => $affiliateId];
    }

    public function getUserIdAction()
    {
        return !empty($_SESSION[ 'uid' ]) ? ['id' => $_SESSION[ 'uid' ]] : [];
    }
}
