<?php

namespace Reseller\Crons;

use Application\Crons\AbstractCron;
use Silex\Application;

abstract class AbstractOldStyleCron extends AbstractCron
{

    /**
     * @var Application
     */
    protected $app;

    public function __construct()
    {
        $args = func_get_args();

        if (isset($args[0])) {
            if (!$args[0] instanceof Application) {
                throw new \RuntimeException('Should be ' . Application::class . ' instance!');
            }

            $this->app = $args[0];
            $this->setApp($args[0]);
        }
    }
}
