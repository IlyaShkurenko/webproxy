<?php
/**
 * @author Dmitriy Kachurovskiy <kd.softevol@gmail.com>
 */

namespace WHMCS\Module\Blazing\Export\GetResponse\Listener;

class AfterModuleTerminateListener extends MarkUserUpdatedListener
{
    protected $name = 'AfterModuleTerminate';
}
