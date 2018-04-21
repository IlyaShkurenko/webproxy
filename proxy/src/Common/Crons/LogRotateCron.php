<?php

namespace Common\Crons;

use Application\Crons\AbstractCron;
use Jobby\InfoException;
use studio24\Rotate\Rotate;

class LogRotateCron extends AbstractCron
{
    protected $config = [
        'schedule' => '0 0 * * *'
    ];
    protected $settings = [
        'dryRun'  => false,
        'count'   => 10,
        'minSize' => '1 MB', // 1 B/1 KB/1 MB/1 GB, means no rotation if log size less
    ];

    protected $logQueue = [];

    public function run()
    {
        if (empty($this->config['output'])) {
            throw new InfoException('"output" cron option is empty, cannot determine logs directory');
        }

        $dir = dirname($this->config['output']);
        $logFiles = [];

        foreach (new \DirectoryIterator($dir) as $file) {
            // Not a regular file
            if ($file->isDot() or $file->isDir() or $file->isLink()) {
                continue;
            }

            // Not a log file
            if (!preg_match('~^[a-z\-]+\.log$~', $file->getFilename())) {
                continue;
            }

            $logFiles[] = $file->getFilename();
        }

        foreach ($logFiles as $file) {
            $rotator = new Rotate("$dir/$file");
            $rotator->setDryRun($this->getSetting('dryRun'));
            if ($this->getSetting('minSize')) {
                $rotator->size($this->getSetting('minSize'));
            }

            $this->logQueue(sprintf('Rotating "%s"...', $file));
            $rotated = $rotator->run();

            // Remove last file, it's the same name
            if ($rotated) {
                $this->logQueue(sprintf('%s files are rotated', join(', ', array_map('basename', $rotated))));
            }
            else {
                $this->logQueue('Not rotated');
            }
        }

        $this->pushLogQueue();

        return true;
    }

    protected function logQueue($log)
    {
        $this->logQueue[] = $log;
    }

    protected function pushLogQueue()
    {
        foreach ($this->logQueue as $log) {
            $this->output($log);
        }

        $this->logQueue = [];
    }
}