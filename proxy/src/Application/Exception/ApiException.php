<?php

namespace Application\Exception;

class ApiException extends \ErrorException
{
    const LOG_ERROR = 'ERROR';
    const LOG_WARN = 'WARN';
    const LOG_INFO = 'INFO';
    const LOG_DEBUG = 'DEBUG';
    const DEFAULT_ERROR_CODE = 'UNKNOWN';
    /**
     * @var array
     */
    private $variables;
    /**
     * @var string
     */
    private $logLevel;
    /** @var string */
    protected $errorCode;

    public function __construct($message, array $variables = [], $logSeverity = self::LOG_ERROR, $code = self::DEFAULT_ERROR_CODE)
    {
        parent::__construct($message, 0, 1);
        $this->variables = $variables;
        $this->logLevel = $logSeverity or $this->logLevel = self::LOG_ERROR;
        $this->errorCode = $code or $this->errorCode = self::DEFAULT_ERROR_CODE;
    }

    /**
     * Get variables
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Get logLevel
     *
     * @return string
     */
    public function getLogLevel()
    {
        return $this->logLevel;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
