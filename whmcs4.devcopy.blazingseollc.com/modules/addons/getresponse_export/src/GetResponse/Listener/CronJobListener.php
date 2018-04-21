<?php

namespace WHMCS\Module\Blazing\Export\GetResponse\Listener;

use WHMCS\Module\Blazing\Export\GetResponse\Command\ExportCommand;
use WHMCS\Module\Blazing\Export\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;

class CronJobListener extends AbstractHookListener
{
    protected $name = 'AfterCronJob';

    /**
     * @param array
     * @return mixed
     */
    protected function execute()
    {
        (new ExportCommand())->export();
    }
}
