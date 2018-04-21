<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use WHMCS\Module\Blazing\Proxy\Seller\RedirectTracker;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Events\AbstractHookListener;

class RedirectAfterOrder extends AbstractHookListener
{
    protected $name = 'ClientAreaPageCart';

    protected function execute(array $args = null)
    {
        // Extract invoice id from referrer
        $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if ($referer and false !== strpos($referer, '/viewinvoice.php')) {
            if (preg_match('~id=(\d+)~', parse_url($referer, PHP_URL_QUERY), $match)) {
                $invoiceId = $match[1];
            }
        }

        // Get if it have been tracked
        if (empty($invoiceId) and $data = RedirectTracker::getTrackedData([], 'invoice')) {
            if (!empty($data['invoiceId'])) {
                $invoiceId = $data['invoiceId'];
            }
        }

        if (!empty($invoiceId)) {
            $service = UserService::findByAnyInvoice($invoiceId);

            if ($service and $service->getRedirectSuccessUrl() and $service->getRedirectFailUrl()) {
                if (in_array($service->getStatus(), [UserService::STATUS_ACTIVE, UserService::STATUS_ACTIVE_UPGRADED])) {
                    header('Location: ' . $service->getRedirectSuccessUrl(), true, 302);
                }
                else {
                    header('Location: ' . $service->getRedirectFailUrl(), true, 302);
                }
                die();
            }
        }
    }
}
