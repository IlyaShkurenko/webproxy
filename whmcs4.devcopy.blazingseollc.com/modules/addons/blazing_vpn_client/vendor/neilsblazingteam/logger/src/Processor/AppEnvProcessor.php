<?php

namespace Blazing\Vpn\Client\Vendor\Blazing\Logger\Processor;

use Blazing\Vpn\Client\Vendor\Blazing\Logger\EnvHelper;
class AppEnvProcessor
{
    protected $appEnv = ['name' => null, 'env' => 'dev', 'owner' => 'any'];
    public function __construct($composerJsonPath = null)
    {
        $this->appEnv = EnvHelper::getAppEnv($composerJsonPath);
    }
    public function __invoke(array $record)
    {
        $record['extra']['app'] = $this->appEnv;
        return $record;
    }
    public function getName()
    {
        return $this->appEnv['name'];
    }
    public function setName($name)
    {
        $this->appEnv['name'] = $name;
    }
    public function getEnv()
    {
        return $this->appEnv['env'];
    }
    public function setEnv($env)
    {
        $this->appEnv['env'] = $env;
    }
    public function getOwner()
    {
        return $this->appEnv['owner'];
    }
    public function setOwner($owner)
    {
        $this->appEnv['owner'] = $owner;
    }
}