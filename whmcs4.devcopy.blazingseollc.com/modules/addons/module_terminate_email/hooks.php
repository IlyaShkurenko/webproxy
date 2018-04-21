<?php

require_once __DIR__ . '/bootstrap.php';

\WHMCS\Module\Framework\ModuleHooks::registerHooks(__FILE__, [
    \WHMCS\Module\Blazing\Notify\EmailAfterModuleTerminate::class
]);
