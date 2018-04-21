<?php

use WHMCS\Module\Blazing\VpsPurchasePageCustomizations\Vendor\WHMCS\Module\Framework\Addon;

require_once __DIR__ . '/bootstrap.php';

return Addon::registerModuleByFile(__FILE__, Addon::configBuilder()
    ->setName('VPS Purchase Page Customizations')
    ->setAuthor('Sprious')
    ->setVersion('1.0', 'stable')
    ->setDescription('
        <ul>
            <li>Removes NS fields on VPS purchase page</li>
            <li>Requires strong password of certain length, and has lower, upper, and symbol</li>
            <li>Hostname entered should not have any symbols or spaces</li>
        </ul>'));