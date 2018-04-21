<?php

namespace Proxy\Assignment\RotationAdviser;

use Application\AppConfig;
use Application\Helper;
use Blazing\Logger\Logger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Vendor\ConfigurableClass\ConfigurableClassTrait;

abstract class AbstractRotationAdviser
{
    use ConfigurableClassTrait {
        getKeyClassConfig as getKeyClassConfigTrait;
    }

    /**
     * @var Connection
     */
    protected $conn;
    /** @var Logger */
    protected $logger;

    protected $queryCache = [];

    protected $classConfigClass = AppConfig::class;
    protected $config = [];

    public function __construct(Connection $conn, Logger $logger = null)
    {
        $this->conn = $conn;
        $this->logger = $logger;
    }

    /**
     * Execute query or get query statement from object cache
     *
     * @param $sql
     * @param array $parameters
     * @param array $types
     * @return \Doctrine\DBAL\Driver\Statement
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getCachedQuery($sql, array $parameters = [], array $types = [])
    {
        $cacheId = md5($sql . json_encode($parameters));

        if (!empty($this->queryCache[ $cacheId ])) {
            $stmt = $this->queryCache[ $cacheId ];
        } else {
            $stmt = $this->conn->executeQuery($sql, $parameters, $types);

            $this->queryCache[ $cacheId ] = $stmt;
        }

        return $stmt;
    }

    protected function getCachedQueryResult($sql, array $parameters, array $types, callable $callback)
    {
        $cacheId = md5($sql . json_encode($parameters));

        // Use cache if possible
        if (!empty($this->queryCache[ $cacheId ])) {
            $stmt = $this->queryCache[ $cacheId ];
            $result = $callback($stmt);

            // Return result or drop cache
            if (false !== $result) {
                return $result;
            }
        }

        // Don't execute query twice if it returns empty result
        if (isset($this->queryCache[ $cacheId ]) and false === $this->queryCache[ $cacheId ]) {
            return false;
        }

        // Make a fresh query
        $stmt = $this->conn->executeQuery($sql, $parameters, $types);
        $this->queryCache[ $cacheId ] = $stmt;

        $result = $callback($stmt);
        if ($result) {
            $this->log('Query executed', ['query' => $sql, 'parameters' => $parameters]);
        }

        // Check fresh query and return false every time of query fails
        if (false === $result) {
            $this->queryCache[ $cacheId ] = false;
        }

        return $result;
    }

    protected function getCachedQueryResultColumn($sql, array $parameters, array $types)
    {
        return $this->getCachedQueryResult($sql, $parameters, $types, function(Statement $conn) {
            return $conn->fetchColumn();
        });
    }

    protected function log($message, array $data = [], $severity = 'debug')
    {
        if ($this->logger) {
            $this->logger->$severity("Adviser:  $message", $data,
                array_merge([],
                    !empty($data['userId']) ? ['userId' => $data['userId']] : []
                )
            );
        }
    }

    protected function injectSql($sql, $type, array $placeholders)
    {
        foreach ($placeholders as $key => $string) {
            $sql = str_replace(":$type" . ucfirst($key), $string, $sql);
        }

        return $sql;
    }

    protected function injectSqls($sql, array $data)
    {
        foreach ($data as $type => $placeholders) {
            $sql = $this->injectSql($sql, $type, $placeholders);
        }

        return $sql;
    }

    protected function getClassDataClassConfig()
    {
        return $this->config;
    }

    protected function getClassClassConfig()
    {
        return AppConfig::class;
    }

    protected function getKeyClassConfig()
    {
        return Helper::hunamizeClassName($this->getKeyClassConfigTrait());
    }
}
