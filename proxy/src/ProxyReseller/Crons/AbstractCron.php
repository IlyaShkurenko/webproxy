<?php

namespace ProxyReseller\Crons;

use Application\Crons\AbstractCron as BaseCron;
use Blazing\Logger\Logger;

abstract class AbstractCron extends BaseCron
{

    public function setLogger(Logger $logger)
    {
        parent::setLogger($logger);
        $logger->configureAppEnvProcessor();
        $logger->setAppName(str_replace('/all', '/reseller-cron', $logger->getAppName()));
    }
}
