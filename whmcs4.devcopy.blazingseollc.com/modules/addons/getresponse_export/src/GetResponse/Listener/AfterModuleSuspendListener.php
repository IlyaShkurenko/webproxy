<?php
/**
 * @author Dmitriy Kachurovskiy <kd.softevol@gmail.com>
 */

namespace WHMCS\Module\Blazing\Export\GetResponse\Listener;

class AfterModuleSuspendListener extends MarkUserUpdatedListener
{
    protected $name = 'AfterModuleSuspend';
}
