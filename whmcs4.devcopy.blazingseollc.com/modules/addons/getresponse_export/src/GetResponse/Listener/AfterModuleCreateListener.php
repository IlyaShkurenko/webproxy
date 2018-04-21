<?php
/**
 * @author Dmitriy Kachurovskiy <kd.softevol@gmail.com>
 */

namespace WHMCS\Module\Blazing\Export\GetResponse\Listener;

class AfterModuleCreateListener extends MarkUserUpdatedListener
{
    protected $name = 'AfterModuleCreate';
}
