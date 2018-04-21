<?php

namespace Proxy\FeedBox;

abstract class AbstractFeedBox
{
    protected $dataCache = [];

    public function push($key, $data)
    {
        $this->doPush($key, $this->toStorageFormat($data));
        $this->dataCache[$key] = $data;

        return $this;
    }

    abstract protected function doPush($key, $data);

    public function supportsPartial()
    {
        return false;
    }

    public function pull($key, $default = null)
    {
        // Use cache if possible
        if (isset($this->dataCache[$key])) {
            return $this->dataCache[$key];
        }

        $data = $this->doPull($key);

        return null !== $data ? $this->fromStorageFormat($data) : $default;
    }

    abstract protected function doPull($key);

    public function cleanCache()
    {
        $this->dataCache = [];
    }

    // Data converters

    protected function toStorageFormat($data)
    {
        return json_encode($data);
    }

    protected function fromStorageFormat($storageData)
    {
        return json_decode($storageData, true);
    }
}
