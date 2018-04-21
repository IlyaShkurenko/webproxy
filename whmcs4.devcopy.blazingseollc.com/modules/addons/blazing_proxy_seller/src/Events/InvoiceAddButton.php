<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Events\AbstractHookListener;

class InvoiceAddButton extends AbstractHookListener
{
    protected $name = 'invoiceBlazingProxyUrl';

    /** @noinspection PhpInconsistentReturnPointsInspection */

    protected function execute(array $args = null)
    {
        $id = $args['invoiceid'];
        $force = $args['force'];

        $service = UserService::findByAnyInvoice($id);
        if ($service and
            (
                in_array($service->getStatus(), [UserService::STATUS_ACTIVE, UserService::STATUS_ACTIVE_UPGRADED]) or
                $force
            ) and
            $service->getRedirectSuccessUrl()
        ) {
            return ['url' => $service->getRedirectSuccessUrl()];
        }
    }
}
