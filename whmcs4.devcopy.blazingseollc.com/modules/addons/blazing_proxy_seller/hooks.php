<?php

use WHMCS\Module\Blazing\Proxy\Seller\Events;
use WHMCS\Module\Framework\ModuleHooks;

require_once __DIR__ . '/bootstrap.php';

ModuleHooks::registerHooks(__FILE__, [
    Events\SyncServiceStatus::class,
    Events\EmailSilencer::class,
    Events\RedirectAfterOrder::class,
    Events\RedirectInvoiceLink::class,
    Events\InvoiceAddButton::class,
    Events\LoginToProxyDashboardLink::class,
    Events\ManualOrderPrice::class,
    Events\ManualOrderHandling::class,
    Events\WorkaroundZeroInvoiceUrl::class,
    Events\InvoicePaid::class,
    Events\InvoiceCreated::class,

    // Integrations
    Events\Integration\FindTrueProductId::class,
    Events\Integration\FindTrueProductPrice::class,
    Events\Integration\FindTrueServiceQuantity::class,
]);