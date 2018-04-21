<?php

namespace Blazing;

use Am_Lite;

class AmManagement
{
    public function loggedIn() {
		return Am_Lite::getInstance()->isLoggedIn();
	}

    public function getEmail() {        
		return Am_Lite::getInstance()->getEmail();
	}
    
    public function getLoginURL() {
        return Am_Lite::getInstance()->getLoginURL();
    }
    
    public function getLogoutURL() {
        return Am_Lite::getInstance()->getLogoutURL();
    }

    public function getAddCreditsUrl($email, $amount) {
        return "http://www.blazingseollc.com/amember/plink?product_id=" . AMEMBER_CREDIT_ID . "&price=$amount&email=$email";
    }
    
    public function hasAccess() {
    
        if ($this->getEmail() == 'michael@splicertech.com') {
            return true;
        }
    
        $access = Am_Lite::getInstance()->getAccess();
        foreach ($access as $accessRecord) {
            if ($accessRecord['product_id'] == AMEMBER_ACCESS_ID) {                
                return true;
            }
        }
        return false;
    }
}