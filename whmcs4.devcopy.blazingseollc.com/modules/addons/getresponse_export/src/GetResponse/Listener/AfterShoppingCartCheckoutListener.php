<?php

namespace WHMCS\Module\Blazing\Export\GetResponse\Listener;

use WHMCS\Billing\Invoice;

class AfterShoppingCartCheckoutListener extends MarkUserUpdatedListener
{
    protected $name = 'AfterShoppingCartCheckout';

    public function execute($args = [])
    {
        if (isset($args['InvoiceID'])) {
            $invoice = Invoice::find($args['InvoiceID']);
            parent::execute(['userid' => $invoice->clientId] + $args);
        }
    }
}
