<?php

namespace WHMCS\Module\Blazing\Export\GetResponse\Listener;

use WHMCS\Billing\Invoice;

class InvoicePaidListener extends MarkUserUpdatedListener
{
    protected $name = 'InvoicePaid';

    public function execute($args = [])
    {
        if (isset($args['invoiceid'])) {
            $invoice = Invoice::find($args['invoiceid']);
            parent::execute(['userid' => $invoice->clientId] + $args);
        }
    }
}
