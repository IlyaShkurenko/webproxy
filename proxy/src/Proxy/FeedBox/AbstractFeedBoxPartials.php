<?php

namespace Proxy\FeedBox;

use ErrorException;

abstract class AbstractFeedBoxPartials extends AbstractFeedBox
{
    protected $rowsPerPush = 2;
    protected $partialRowsQueue = [];
    protected $partialKeysInit = [];

    public function setRowsPerPush($count)
    {
        $this->rowsPerPush = $count;

        return $this;
    }

    public function supportsPartial()
    {
        return true;
    }

    public function startPartialQueue($key)
    {
        // Only if has not been
        if (!in_array($key, $this->partialKeysInit)) {
            $this->partialKeysInit[] = $key;
        }
    }

    public function endPartialQueue($key)
    {
        if(in_array($key, $this->partialKeysInit) && empty($this->partialRowsQueue[$key])) {
            return false;
        }

        $this->forcePush($key);

        return true;
    }

    public function endAllPartialQueues()
    {
        foreach ($this->partialKeysInit as $key) {
            $this->endPartialQueue($key);
        }
    }

    public function pushPartial($key, $row)
    {
        if (!$this->supportsPartial())
        {
            throw new ErrorException('This FeedBox does not supports partials');
        }

        $this->partialRowsQueue[$key][] = $this->toStorageFormat($row);

        if (count($this->partialRowsQueue[$key]) >= $this->rowsPerPush) {
            $this->forcePush($key);
        }
    }

    public function pushSinglePartialQueue($key, $row)
    {
        if (!in_array($key, $this->partialKeysInit)) {
            $this->endSinglePartialQueue();
            $this->startPartialQueue($key);
        }

        $this->pushPartial($key, $row);
    }

    public function endSinglePartialQueue()
    {
        // Validate
        if (1 < count($this->partialKeysInit)) {
            throw new ErrorException(sprintf(
                'Can not handle multiple partials in single mode (%s)',
                join(', ', $this->partialKeysInit)));
        }

        // Flush previous queue
        elseif (1 == count($this->partialKeysInit)) {
            $this->endPartialQueue(array_values($this->partialKeysInit)[0]);
        }
    }

    protected function forcePush($key)
    {
        $init = false;
        if (in_array($key, $this->partialKeysInit)) {
            $init = true;
            unset($this->partialKeysInit[array_search($key, $this->partialKeysInit)]);
        }

        $this->doPushPartial($key, $this->partialRowsQueue[$key], $init);
        $this->partialRowsQueue[$key] = [];
    }

    public function pullPartialAll($key, $default = [])
    {
        $rows = [];

        while ($row = $this->doPullPartial($key)) {
            $rows[] = $this->fromStorageFormat($row);
        }

        return $rows ? $rows : $default;
    }

    public function pullPartialRow($key)
    {
        $row = $this->doPullPartial($key);

        if (false !== $row) {
            $row = $this->fromStorageFormat($row);
        }
        else {
            $row = null;
        }

        return $row;
    }

    abstract protected function doPushPartial($key, $row, $init = false);

    abstract protected function doPullPartial($key);
}
