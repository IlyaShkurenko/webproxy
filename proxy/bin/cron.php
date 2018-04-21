<?php

use Application\Helper;
use Axelarge\ArrayTools\Arr;
use Jobby\ScheduleChecker;
use Silex\Application;
use Symfony\Component\Console;
use Vendor\Jobby\Jobby;

require __DIR__ . '/../app/cron.php';

/** @var \Application\Crons\AbstractCron[] $crons */
$crons = [
    // Top cron processes - lightweight, priority
    new \Common\Crons\LogRotateCron(),

    // Monitoring - lightweight, priority
    new Proxy\Crons\MonitoringNoProxiesCron(),
    new Proxy\Crons\ServerCheckRotatingPortsCron(),

    // Maintenance - lightweight
    new Proxy\Crons\ProxyBumpLastUsedCron(),
    new Proxy\Crons\ProxyRemovePrimaryCron(),
    new \Common\Crons\UserSyncAmemberIdCron(),
    new \Common\Crons\UserSyncAmemberLoginCron(),
    new \Common\Crons\UserGenerateLoginCron(),
    new Proxy\Crons\ProxyLiberateBlocksCron(),
    new Proxy\Crons\ProxyPushToRotatingCron(),

    // Reseller - lightweight
    new Reseller\Crons\ResellerProcessPayment(),
    new Reseller\Crons\ResellerChargeAccount(),
    new ProxyReseller\Crons\ProxyResellerChargeForPackagesCron(),
    new ProxyReseller\Crons\ProxyResellerFetchPayments(),

    // Assignment - priority
    new Proxy\Crons\UserResetReplacesCounterCron(),
    new Proxy\Crons\PortsSyncPlansCron(),
    new Proxy\Crons\PortsAssignProxiesCron(),
    new Proxy\Crons\PortsAssignProxiesIPv6Cron(),
    new Proxy\Crons\PortsReassignRotatingProxiesCron(),
    new Proxy\Crons\PortsReassignBadProxiesCron(),
    new Proxy\Crons\FeedPlistCron(),
    new Proxy\Crons\FeedPlistCleanupCron(),

    // Heavy crons
    new Proxy\Crons\ProxyCheckConnectNewCron(),
    new Proxy\Crons\ProxyCheckConnectBad(),
    new Proxy\Crons\ProxyCheckConnectActiveCron(),
    new Proxy\Crons\ProxyCheckConnectDead(),
    new Proxy\Crons\ProxyCheckGeoCron(),
    new Proxy\Crons\ProxyCheckConnectCron(),
];

/** @noinspection PhpUndefinedVariableInspection */
(new Console\Application('cron', '1.0'))
    ->register('run')
        ->addOption('command', 'c', Console\Input\InputOption::VALUE_OPTIONAL,
            sprintf('Command name, like %s (for class %s). Available commands: ' . PHP_EOL . '%s',
                \Application\Crons\AbstractCron::humanizeCronClass(Proxy\Crons\PortsSyncPlansCron::class),
                Helper::getClassBasename(Proxy\Crons\PortsSyncPlansCron::class),
                join(PHP_EOL, array_map(function(\Application\Crons\AbstractCron $cron) {
                    return sprintf('- %s, schedule: %s', $cron->getHumanName(), $cron->getMergedConfig()['schedule']);
                }, $crons))
            )
        )
        ->addOption('force', 'f', Console\Input\InputOption::VALUE_NONE,
            'Currently only used for forcing execution of single command if it is not enabled')
        ->addOption('force-enable', 'e', Console\Input\InputOption::VALUE_NONE,
            'Difference with "force" options is that this option only set enable option to true, 
            and respects the schedule')
        ->setCode(function(Console\Input\InputInterface $input) use (&$crons, $app) {
            ini_set('display_errors', 1);

            $workingCrons = array_merge([], $crons);

            // Single command
            if ($singleCron = $input->getOption('command')) {

                $found = false;
                foreach ($crons as $cron) {
                    if (strtolower(\Application\Crons\AbstractCron::cronizeHumanString($singleCron)) == strtolower($cron->getName()) or
                        strtolower(\Application\Crons\AbstractCron::cronizeHumanString($singleCron)) == strtolower($cron->getName() . 'cron')) {
                        // Good, found
                        $found = $cron;
                        break;
                    }
                }

                if (!$found) {
                    throw new ErrorException(sprintf('Command "%s" is unknown. Perhaps you mean "%s"? Known commands: "%s"',
                        $input->getOption('command'),
                        Arr::wrap($crons)
                            ->map(function(\Application\Crons\AbstractCron $cron) use ($singleCron) {
                                return [
                                    'similar' => levenshtein($singleCron, $cron->getHumanName()),
                                    'keyword' => $cron->getHumanName()
                                ];
                            })
                            ->minBy(function($data) { return $data['similar']; })['keyword'],
                        join('", "', array_map(\Application\Crons\AbstractCron::class . '::humanizeCronClass', $crons))
                    ));
                }

                // Use last found cron
                $workingCrons = [$found];
            }

            $jobby = new Jobby();
            $scheduleChecker = new ScheduleChecker();
            $logsDir = $app['config.logs.path'] . '/cron';

            foreach ($workingCrons as $cron) {
                // Local scope
                $dir = __DIR__;

                /** @var \Application\Crons\AbstractCron $cron */
                $config = array_merge(
                    // Set initial settings (can be overridden)
                    [
                    'output' => $logsDir . '/' . \Application\Crons\AbstractCron::humanizeCronClass($cron) . '.log'
                ],
                    $cron->getMergedConfig()
                );

                // Force options
                if (count($workingCrons) != count($crons)) {
                    if ($input->getOption('force')) {
                        $config['enabled'] = true;
                        $config['schedule'] = '* * * * *';
                    }
                    if ($input->getOption('force-enable')) {
                        $config['enabled'] = true;
                    }

                    if (!$config['enabled']) {
                        throw new ErrorException(sprintf(
                            'Cron job "%s" is not enabled. Use --force or --force-enable ' .
                            'option to execute it anyway', $cron->getHumanName()));
                    }

                    if (!$scheduleChecker->isDue($config['schedule'])) {
                        throw new ErrorException(sprintf(
                            'Cron job "%s" schedule is not match run time. Use --force ' .
                            'option to execute it anyway', $cron->getHumanName()));
                    }
                }

                $jobby->add($cron->getName(), array_merge($config, [
                    'command' => function($logger = null) use ($cron, $dir, $config) {
                        ini_set('display_errors', 1);

                        // Bootstrap the application
                        /** @noinspection PhpIncludeInspection */
                        /** @var Application $app */
                        $app = require "$dir/../app/cli.php";
                        $cron->setApp($app);

                        // Resulting verse of the config
                        $cron->setConfig($config);

                        // Use logger
                        if ($logger) {
                            $cron->setLogger($logger);
                        }

                        // Here we go
                        /** AbstractCron $cron */
                        return $cron->run();
                    }
                ]));
            }

            // Ensure logs dir is exists
            if (!is_dir($logsDir)) {
                mkdir($logsDir, 770, true);
            }

            // Limitless script
            set_time_limit(0);

            // Here we go
            $jobby->run();
        })
    ->getApplication()
    ->setDefaultCommand('run', true)
    ->run();