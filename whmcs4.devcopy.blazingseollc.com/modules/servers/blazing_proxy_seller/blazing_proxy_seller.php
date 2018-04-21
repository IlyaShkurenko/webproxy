<?php

use WHMCS\Module\Blazing\Proxy\Seller\Events;
use WHMCS\Module\Framework\Addon;
use WHMCS\Module\Framework\Events\CallbackModuleListener;
use WHMCS\Module\Framework\Server;

require __DIR__ . '/../../addons/blazing_proxy_seller/bootstrap.php';

if (!Addon::getInstanceById('blazing_proxy_seller')->isEnabled()) {
    return false;
}

return Server::registerModuleByFile(__FILE__, [
  'DisplayName' => 'Blazing Proxy',
])->registerModuleListeners([
    Events\ServiceSuspend::class,
    Events\ServiceUnsuspend::class,
    Events\ServiceTerminate::class,
    Events\ServiceCreate::class,
    Events\ServiceUpgrade::class,
    Events\ServiceUpdateQuantity::class,
    CallbackModuleListener::createCallback('AdminCustomButtonArray', function() {
        return [
            'Upgrade to New Quantity' => 'UpdateQuantity'
        ];
    }),
]);