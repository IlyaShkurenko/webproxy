<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use WHMCS\Module\Blazing\Proxy\Seller\RedirectTracker;

class WorkaroundZeroInvoiceUrl extends RedirectAfterOrder
{
    protected $name = 'ClientAreaPage';

    protected function execute(array $args = null)
    {
        if (false !== stripos($_SERVER['REQUEST_URI'], 'viewinvoice.php') and empty($_GET['id'])) {

            // Extract invoice id from referrer
            $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            if ($referer and false !== strpos($referer, '/viewinvoice.php')) {
                if (preg_match('~id=(\d+)~', parse_url($referer, PHP_URL_QUERY), $match)) {
                    $invoiceId = $match[1];
                }
            }

            // Get if it have been tracked
            if (empty($invoiceId) and $data = RedirectTracker::getTrackedData([], 'invoice', false)) {
                if (!empty($data['invoiceId'])) {
                    $invoiceId = $data['invoiceId'];
                }
            }

            if (!empty($invoiceId)) {
                header("Location: viewinvoice.php?id=$invoiceId", true, 302);
            }
        }
    }
}
