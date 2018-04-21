<?php

use Blazing\Vpn\Client\Events\Client\ClientDashboard;
use Blazing\Vpn\Client\Events\Client\DownloadKey;
use Blazing\Vpn\Client\Events\ServiceCreate;
use Blazing\Vpn\Client\Events\ServiceSuspend;
use Blazing\Vpn\Client\Events\ServiceTerminate;
use Blazing\Vpn\Client\Events\ServiceUnsuspend;
use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\CallbackModuleListener;
use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Server;

require_once __DIR__ . "/../../addons/blazing_vpn_client/bootstrap.php";

return Server::registerModuleByFile(__FILE__, Server::configBuilder()
    ->setName('Blazing VPN Client')
    ->setApiVersion('0.5'))
    ->registerModuleListeners([
        ServiceCreate::class,
        ServiceSuspend::class,
        ServiceUnsuspend::class,
        ServiceTerminate::class,
        ClientDashboard::class,
        DownloadKey::class,
        CallbackModuleListener::createCallback('ClientAreaAllowedFunctions', function() {
            return [DownloadKey::NAME];
        })
    ]);