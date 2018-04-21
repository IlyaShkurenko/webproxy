<?php

namespace Application\Crons;

use Application\AppConfig;
use Application\Helper;
use Blazing\Logger\Logger;
use Common\Events\Emitter;
use Doctrine\DBAL\Connection;
use Silex\Application;
use Vendor\ConfigurableClass\ConfigurableClassTrait;

abstract class AbstractCron
{
    use ConfigurableClassTrait {
        getKeyClassConfig as getKeyClassConfigTrait;
    }

    /** @var Application */
    protected $app;

    protected $defaultConfig = [
        'schedule'    => '* * * * *',
        'maxRuntime'  => 60 * 1,
        'enabled'     => true,
        'mailer'      => null,
        'recepients'  => '',
        'loggerClass' => Logger::class
    ];

    /**
     * Cron config, includes settings
     *
     * @var array
     */
    protected $config = [];

    /**
     * Settings are included in config as config.settings
     *
     * @var array
     */
    protected $settings = [];

    protected $dbConnMap = [
        'default'    => 'proxy',
        'unbuffered' => 'proxy_unbuffered',
        'rs'         => 'reseller',
        'am'         => 'amember'
    ];

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct()
    {

    }

    public function setApp(Application $app)
    {
        $this->app = $app;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    // Cron stuff

    public function getName()
    {
        return Helper::getClassBasename($this);
    }

    public function getHumanName()
    {
        return self::humanizeCronClass($this);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getMergedConfig()
    {
        return $this->getMergedDataClassConfig();
    }

    // File config

    protected function getClassDataClassConfig()
    {
        $defaultConfig = $this->getMergedDataForClassConfig(__CLASS__, $this->defaultConfig);

        return array_replace_recursive(
            $defaultConfig,
            $this->getConfig(),
            ['settings' => $this->loadSettings()]
        );
    }

    protected function getClassClassConfig()
    {
        return AppConfig::class;
    }

    protected function getNameClassConfig()
    {
        return 'cron';
    }

    protected function getKeyClassConfig($class = null)
    {
        return self::humanizeCronClass($this->getKeyClassConfigTrait($class));
    }

    protected function prepareClassDataClassConfig(array $config)
    {
        unset($config['mailer']);
        unset($config['recepients']);
        unset($config['output']);
        unset($config['loggerClass']);

        if (empty($config['settings'])) {
            unset($config['settings']);
        }

        return $config;
    }

    abstract public function run();

    // Settings

    protected function loadSettings()
    {
        return $this->settings;
    }

    public function getSetting($key)
    {
        return $this->getFromConfig("settings.$key");
    }

    // Shortcuts

    /**
     * Get Silex application
     *
     * @return Application
     */
    protected function getApp()
    {
        if (!$this->app) {
            throw new \RuntimeException('App has not set to ' . get_class($this));
        }

        return $this->app;
    }

    /**
     * Get connection
     *
     * @param string $type
     * @return Connection
     */
    protected function getConn($type = '')
    {

        return $this->getApp()[ 'dbs' ][ !empty($this->dbConnMap[ $type ]) ?
            $this->dbConnMap[ $type ] :
            $this->dbConnMap[ 'default' ]
        ];
    }

    // Helpers

    protected function output($string)
    {
        echo $string;
        ob_flush();
    }

    /**
     * @return Emitter
     */
    protected function getEvents()
    {
        return $this->getApp()['events'];
    }

    // Log

    protected function log($message, array $variables = [], array $indexes = [])
    {
        $this->logRecord('info', $message, $variables, $indexes);
    }

    protected function debug($message, array $variables = [], array $indexes = [])
    {
        $this->logRecord('debug', $message, $variables, $indexes);
    }

    protected function notice($message, array $variables = [], array $indexes = [])
    {
        $this->logRecord('notice', $message, $variables, $indexes);
    }

    protected function warn($message, array $variables = [], array $indexes = [])
    {
        $this->logRecord('warning', $message, $variables, $indexes);
    }

    protected function logRecord($level, $message, array $variables = [], array $indexes = [])
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $variables, $indexes);
        }
        else {
            $this->output(sprintf('%s %s []', $message, json_encode(array_merge($variables, ['$index' => $indexes]))));
        }
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        $logger->configureAppEnvProcessor();
        $logger->setAppName(str_replace('/all', '/cron', $logger->getAppName()));
    }

    // Utils

    public static function humanizeCronClass($className)
    {
        return Helper::hunamizeClassName(str_replace('Cron', '', Helper::getClassBasename($className)));
    }

    /**
     * Funny name, yeah
     *
     * @param $string
     * @return string
     */
    public static function cronizeHumanString($string)
    {
        return Helper::robotizeHumanClassName($string) . 'Cron';
    }
}
