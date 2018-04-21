<?php

use WHMCS\Module\Blazing\Export\Vendor\WHMCS\Module\Framework\ModuleHooks;

require_once __DIR__ . '/bootstrap.php';

ModuleHooks::registerHooks(
    __FILE__,
    [
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\ClientEditListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\AddTransactionListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\AfterProductUpgradeListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\ServiceDeleteListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\ServiceEditListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\ServiceRecurringCompletedListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\AfterModuleChangePackageListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\AfterModuleCreateListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\AfterModuleSuspendListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\AfterModuleTerminateListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\AfterModuleUnsuspendListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\CronJobListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\AddInvoicePaymentListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\InvoicePaidListener::class,
        \WHMCS\Module\Blazing\Export\GetResponse\Listener\AfterShoppingCartCheckoutListener::class,
    ]
);
