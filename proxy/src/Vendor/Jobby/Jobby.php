<?php

namespace Vendor\Jobby;

use Jobby\Exception;
use Jobby\Helper;

class Jobby extends \Jobby\Jobby
{
    protected $currentTime = 0;

    public function __construct(array $config = [])
    {
        $this->currentTime = time();
        parent::__construct($config);
    }

    public function getDefaultConfig()
    {
        return array_replace_recursive(parent::getDefaultConfig(), [
            'jobClass' => BackgroundJob::class,
            'debug' => true,
            'phpBinWindows' => 'php'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $isUnix = ($this->helper->getPlatform() === Helper::UNIX);

        if ($isUnix && !extension_loaded('posix')) {
            throw new Exception('posix extension is required');
        }

        $scheduleChecker = new ScheduleChecker();
        foreach ($this->jobs as $job => $config) {
            if (!$scheduleChecker->isDueTime($config['schedule'], $this->currentTime)) {
                continue;
            }
            if ($isUnix) {
                $this->runUnix($job, $config);
            } else {
                $this->runWindows($job, $config);
            }
        }
    }

    protected function runUnix($job, array $config)
    {
        $this->doRun($job, $config, function($callback) use ($job, $config) {
            $command = $this->getExecutableCommand($job, $config);
            $binary = $this->getPhpBinary();

            // http://stackoverflow.com/a/6144213
            flush();
            $process = proc_open("$binary $command", [
                0 => ["pipe", "r"],   // stdin is a pipe that the child will read from
                1 => ["pipe", "w"],   // stdout is a pipe that the child will write to
                2 => ["pipe", "w"]    // stderr is a pipe that the child will write to
            ], $pipes, realpath('./'), []);
            if (is_resource($process)) {
                while ($line = fgets($pipes[1])) {
                    $callback($line);
                    flush();
                }
            }
            proc_close($process);
        });
    }

    protected function runWindows($job, array $config)
    {
        $this->doRun($job, $config, function($callback) use ($job, $config) {
            $command = $this->getExecutableCommand($job, $config);
            $binary = $this->config['phpBinWindows'];

            // Idea from http://stackoverflow.com/a/17659276
            flush();
            $process = popen("$binary $command", 'r');
            if (is_resource($process)) {
                while (!feof($process)) {
                    $line = fgets($process);
                    $callback($line);

                    flush();
                    sleep(0.1);
                }
            }
            pclose($process);
        });
    }

    protected function doRun($job, array $config, callable $callback)
    {
        $this->handleHeaderOutput($config, $job);

        $hasLines = false;
        $callback(function ($line) use ($config, &$hasLines) {
            $this->handleBodyOutput($config, $line, true);
            $hasLines = true;
        });
        if ($hasLines) {
            $this->handleBodyOutput($config, ' ', true);
            $this->handleBodyOutput($config, ' ', true);
        }
        elseif (!$config['enabled']) {
            $this->handleBodyOutput($config, '');
        }
    }

    protected function handleHeaderOutput(array $config, $job)
    {
        $this->handleOutput($config, sprintf('### Cron "%s" [%s]...', $job, date('Y-m-d H:i:s')) . PHP_EOL);
    }

    protected function handleBodyOutput($config, $content, $line = false)
    {
        // Not enabled
        if (!$config['enabled'] and !$content) {
            $content = ["Cron job is not enabled"];
        }

        // Output content
        if ($content) {
            $content = (array) $content;

            if (!$line) {
                $this->handleOutput($config, PHP_EOL . trim(join(PHP_EOL, $content)) . PHP_EOL . PHP_EOL);
            }
            else {
                $this->handleOutput($config, PHP_EOL . trim(join(PHP_EOL, $content)));
            }
        }
    }

    protected function handleOutput(array $config, $output)
    {
        if ($config['debug'] and (trim($output) or PHP_EOL === $output)) {
            echo $output;
        }
    }
}