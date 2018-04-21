<?php
/**
 * @author Dmitriy Kachurovskiy <kd.softevol@gmail.com>
 */

namespace WHMCS\Module\Blazing\Export\GetResponse\Listener;

class AfterModuleUnsuspendListener extends MarkUserUpdatedListener
{
    protected $name = 'AfterModuleUnsuspend';
}
