<?php

namespace Vendor\Jobby;

use Jobby\Exception;
use Monolog\Logger;
use RuntimeException;
use Vendor\Jobby\Logger\Handler\CallbackHandler;
use Whoops\Exception\Formatter;
use Whoops\Exception\Inspector;

class BackgroundJob extends \Jobby\BackgroundJob
{

    /** @var Helper */
    protected $helper;

    public function __construct($job, array $config, Helper $helper = null)
    {
        parent::__construct($job, $config, !$helper ? new Helper() : $helper);
    }

    public function run()
    {
        // Set max time
        if ($this->config['maxRuntime']) {
            set_time_limit((int) $this->config['maxRuntime']);
        }

        parent::run();
    }

    protected function checkMaxRuntime($lockFile)
    {
        if (!$this->config['maxRuntime']) {
            return;
        }

        $runtime = $this->helper->getLockLifetime($lockFile);
        if ($runtime < $this->config['maxRuntime']) {
            return;
        }

        $pid = $this->helper->getLockPid($lockFile);
        if ($this->helper->killLockedProcess($lockFile)) {
            $this->log("Process \"$pid\" has been killed");
            return;
        }

        throw new Exception("MaxRuntime of {$this->config['maxRuntime']} secs exceeded! Current runtime: $runtime secs");
    }

    protected function runFunction()
    {
        $command = $this->getSerializer()->unserialize($this->config['closure']);

        // Output as soon as it received
        $logStarted = false;
        $startTime = time();
        $startLog = function() use (&$logStarted, &$startTime) {
            if ($logStarted) {
                return;
            }

            $pid = getmypid();
            $this->writeLog("Job \"{$this->job}\" ($pid):" . PHP_EOL, true, $startTime);
            $logStarted = true;
        };

        $ignoreNextLine = false;
        ob_start(function($content) use ($startLog, &$ignoreNextLine) {
            if (!trim($content)) {
                return;
            }

            if (!$ignoreNextLine) {
                $startLog();
                $this->writeLog($content);
            }
            $ignoreNextLine = false;

            if ($this->config['debug'] and trim($content)) {
                return rtrim($content) . PHP_EOL;
            }
        });

        // Construct logger
        $logger = null;
        if (!empty($this->config['loggerClass'])) {
            $loggerCallbackHandler = new CallbackHandler();
            $loggerCallbackHandler->setCallback(function(array $record) use ($startLog, &$ignoreNextLine) {
                $startLog();
                $this->logRecord($record);

                if ($this->config['debug']) {
                    $ignoreNextLine = true;
                    echo "[{$record['level_name']}]\t{$record['message']}" .
                        ($record['context'] ? (' ' . json_encode($record['context'])) : '');
                    ob_flush();
                }
            });

            $logger = new $this->config['loggerClass']([$loggerCallbackHandler]);

            if (!$logger instanceof Logger) {
                throw new RuntimeException('Logger should be inherited from ' . Logger::class);
            }
        }

        $retval = null;
        try {
            $retval = $command($logger);

            if ($retval !== true) {
                throw new Exception("Closure did not return true! Returned:\n" . print_r($retval, true));
            }
        } catch (\Exception $e) {

        }
        ob_end_clean();

        // Just rethrow previous exception
        if (isset($e)) {
            throw new Exception(
                Formatter::formatExceptionPlain(new Inspector($e)),
                $e->getCode(), $e
            );
        }
    }

    protected function log($message)
    {
        if ($this->config['debug']) {
            echo $message . PHP_EOL;
        }

        $this->writeLog($message);
    }

    protected function writeLog($message, $prependNewline = false, $time = null)
    {
        $now = date($this->config['dateFormat'], $time ? $time : time());

        if ($logfile = $this->getLogfile()) {
            file_put_contents($logfile, ($prependNewline ? PHP_EOL : '') . "[$now] $message\n", FILE_APPEND);
        }
    }

    protected function logRecord(array $loggerRecord)
    {
        if ($logfile = $this->getLogfile()) {
            file_put_contents($logfile, $loggerRecord['formatted'], FILE_APPEND);
        }
    }
}
