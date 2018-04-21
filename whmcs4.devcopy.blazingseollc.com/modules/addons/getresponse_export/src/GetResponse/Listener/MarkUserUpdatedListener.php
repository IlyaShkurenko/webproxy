<?php
/**
 * @author Dmitriy Kachurovskiy <kd.softevol@gmail.com>
 */

namespace WHMCS\Module\Blazing\Export\GetResponse\Listener;

use WHMCS\Module\Blazing\Export\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
use WHMCS\Module\Blazing\Export\Vendor\WHMCS\Module\Framework\Helper;

abstract class MarkUserUpdatedListener extends AbstractHookListener
{
    public function execute($args = [])
    {
        if (isset($args['params'])) {
            $args = $args['params'];
        }
        logActivity(sprintf('Hook with name %s has been called with arguments %s.', $this->getName(), json_encode($args)), 0);

        Helper::conn()
            ->table('getresponseexport_exported_clients')
            ->where('client_id', $args['userid'])
            ->where('status',0)
            ->update(['status' => 1, 'updated_at' => date('Y-m-h H:i:s')]);
        Helper::restoreDb();
    }
}