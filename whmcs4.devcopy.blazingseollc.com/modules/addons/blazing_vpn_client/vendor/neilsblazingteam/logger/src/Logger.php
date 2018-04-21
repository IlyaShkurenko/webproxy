<?php

namespace Blazing\Vpn\Client\Vendor\Blazing\Logger;

use Blazing\Vpn\Client\Vendor\Blazing\Logger\Formatter\RequestIdLineFormatter;
use Blazing\Vpn\Client\Vendor\Blazing\Logger\Processor\AppEnvProcessor;
use Blazing\Vpn\Client\Vendor\Blazing\Logger\Processor\MasterRequestUidProcessor;
use Blazing\Vpn\Client\Vendor\Blazing\Logger\Processor\RequestUidProcessor;
use Blazing\Vpn\Client\Vendor\Monolog\Handler\HandlerInterface;
use Blazing\Vpn\Client\Vendor\Monolog\Handler\RotatingFileHandler;
use Blazing\Vpn\Client\Vendor\Monolog\Logger as Monolog;
class Logger extends Monolog
{
    protected $microsecondTimestamps = true;
    protected $sharedIndex = [];
    /** @var RequestUidProcessor */
    protected $uidProcessor;
    /** @var MasterRequestUidProcessor */
    protected $mUidProcessor;
    /** @var AppEnvProcessor */
    protected $appEnvProcessor;
    protected $lastIndex = [];
    // --- Constructors
    public static function createRotatingFileLogger($path, $rotatingCount = 30, $rotatingDateFormat = 'Y-m-d')
    {
        $rotatingHandler = new RotatingFileHandler($path, $rotatingCount);
        $rotatingHandler->setFilenameFormat('{filename}-{date}', $rotatingDateFormat);
        return new static([$rotatingHandler]);
    }
    public function __construct(array $handlers = [], array $processors = [])
    {
        parent::__construct('', array_map(function (HandlerInterface $handler) {
            $handler->setFormatter(new RequestIdLineFormatter());
            return $handler;
        }, $handlers), array_merge([$this->uidProcessor = new RequestUidProcessor(), $this->mUidProcessor = new MasterRequestUidProcessor($this)], $processors));
    }
    public function configureAppEnvProcessor($composerJsonPath = null)
    {
        if ($this->appEnvProcessor) {
            throw new \RuntimeException('Cannot initialize AppEnv processor twice');
        }
        $this->pushProcessor($this->appEnvProcessor = new AppEnvProcessor($composerJsonPath));
    }
    // --- Context methods
    public function getRequestUid()
    {
        return $this->uidProcessor->getUid();
    }
    public function setRequestUid($uid)
    {
        $this->uidProcessor->setUid($uid);
    }
    public function getMasterRequestUid()
    {
        return $this->mUidProcessor->getUid();
    }
    public function setMasterRequestUid($uid)
    {
        $this->mUidProcessor->setUid($uid);
    }
    public function prepareMasterRequestParameter()
    {
        return $this->mUidProcessor->prepareRequestParameter();
    }
    public function getAppName()
    {
        if (!$this->appEnvProcessor) {
            throw new \RuntimeException('AppEnv processor is not configured yet');
        }
        return $this->appEnvProcessor->getName();
    }
    public function setAppName($name)
    {
        if (!$this->appEnvProcessor) {
            throw new \RuntimeException('AppEnv processor is not configured yet');
        }
        $this->appEnvProcessor->setName($name);
        return $this;
    }
    public function setAppEnv($env)
    {
        if (!$this->appEnvProcessor) {
            throw new \RuntimeException('AppEnv processor is not configured yet');
        }
        $this->appEnvProcessor->setEnv($env);
        return $this;
    }
    public function setAppOwner($owner)
    {
        if (!$this->appEnvProcessor) {
            throw new \RuntimeException('AppEnv processor is not configured yet');
        }
        $this->appEnvProcessor->setOwner($owner);
        return $this;
    }
    // --- Log methods
    public function addRecord($level, $message, array $context = [], array $index = [])
    {
        $context = $this->prepareContext($context, array_merge($this->lastIndex, $index));
        $this->lastIndex = [];
        return parent::addRecord($level, $message, $context);
    }
    public function log($level, $message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::log($level, $message, $context);
    }
    public function debug($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::debug($message, $context);
    }
    public function info($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::info($message, $context);
    }
    public function notice($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::notice($message, $context);
    }
    public function warn($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::warn($message, $context);
    }
    public function warning($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::warning($message, $context);
    }
    public function err($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::err($message, $context);
    }
    public function error($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::error($message, $context);
    }
    public function crit($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::crit($message, $context);
    }
    public function critical($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::critical($message, $context);
    }
    public function alert($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::alert($message, $context);
    }
    public function emerg($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::emerg($message, $context);
    }
    public function emergency($message, array $context = [], array $index = [])
    {
        $this->lastIndex = $index;
        return parent::emergency($message, $context);
    }
    // Indexes
    protected function prepareContext(array $context, array $index)
    {
        foreach ($index as $key => $value) {
            if ($this->validateIndex($key, $value)) {
                $index[$key] = $this->castIndex($key, $value);
            }
        }
        $indexes = array_merge($this->sharedIndex, $index);
        return array_merge($context, $indexes ? ['$index' => $indexes] : []);
    }
    public function addSharedIndex($key, $value)
    {
        if ($this->validateIndex($key, $value)) {
            $this->sharedIndex[$key] = $this->castIndex($key, $value);
        }
        return $this;
    }
    public function getSharedIndex($key)
    {
        return isset($this->sharedIndex[$key]) ? $this->sharedIndex[$key] : null;
    }
    public function removeSharedIndex($key)
    {
        unset($this->sharedIndex[$key]);
        return $this;
    }
    protected function castIndex($key, $value)
    {
        return (string) $value;
    }
    protected function validateIndex($key, $value)
    {
        return !(is_null($value) or is_bool($value));
    }
}