<?php

namespace Proxy\Assignment\RotationAdviser;

use Blazing\Logger\Logger;
use Proxy\Assignment\Port\PortInterface;

class SpecialCustomerRuleBuilder
{
    protected $name;

    /** @var array */
    protected $condition;

    /** @var array */
    protected $matchedCondition;

    /** @var callable */
    protected $conditionHandler;

    /** @var callable */
    protected $handler;

    /** @var bool|callable */
    protected $fallbackDeterminator;

    protected $customData = [];

    /** @var callable[] */
    protected $customFunctions = [];

    /** @var Logger */
    protected $logger;

    public static function getBuilder()
    {
        return new static();
    }

    /**
     * This name will be used in logs
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setCondition($users, $country, $category)
    {
        return $this->setCustomCondition([
            'users'    => (array) $users,
            'country'  => $country,
            'category' => $category
        ], function(PortInterface $port, array $conditions) {
            return (in_array($port->getUserId(), $conditions['users']) and
                $conditions['country'] == $port->getCountry() and
                $conditions['category'] == $port->getCategory()) ?
                $conditions : false;
        });
    }

    /**
     * Custom condition check
     * @param array $conditions Configuration/context, is passed to the next argument
     * @param callable $checkConditions function(PortInterface $port, array $conditions, self $builder) {}
     * @return $this
     */
    public function setCustomCondition(array $conditions, callable $checkConditions)
    {
        $self = $this;
        $this->condition = $conditions;
        $this->conditionHandler = function(PortInterface $port) use (&$self, $conditions, $checkConditions) {
            $result = $checkConditions($port, $conditions, $self);
            $self->matchedCondition = $result ? (array) $result : false;

            return $result ? true : false;
        };

        return $this;
    }

    public function getLastMatchedCondition()
    {
        return $this->matchedCondition;
    }

    /**
     * Should return proxy id
     * @param callable $handler function(PortInterface $port, array $matchedConditions, self $builder) {}
     * @return $this
     */
    public function setHandler(callable $handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * @param bool|callable $fallback
     * @return $this
     */
    public function setFallbackToCommonAdviser($fallback)
    {
        $this->fallbackDeterminator = $fallback;

        return $this;
    }

    /**
     * Get be retrieved in custom functions
     * @param $name
     * @param array $data
     * @return $this
     */
    public function setCustomData($name, array $data)
    {
        $this->customFunctions[ $name ] = $data;

        return $this;
    }

    public function getCustomData($name)
    {
        if (empty($this->customData[$name])) {
            throw new \RuntimeException("No custom data \"$name\"");
        }

        return $this->customData[$name];
    }

    /**
     * Can be called by ->callCustomFunction('callbackName', ['data'])
     * @param $name
     * @param callable $callback function($arguments, self $builder) {}
     * @return $this
     */
    public function setCustomFunction($name, callable $callback)
    {
        $this->customFunctions[ $name ] = $callback;

        return $this;
    }

    public function callCustomFunction($name, array $data = [])
    {
        if (empty($this->customFunctions[$name])) {
            throw new \RuntimeException("No custom callback \"$name\"");
        }

        return $this->customFunctions[$name]($data, $this);
    }

    /**
     * Defined automatically
     *
     * @param Logger $logger
     * @return $this
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    public function build()
    {
        if (!$this->conditionHandler or !$this->handler) {
            throw new \RuntimeException('No condition handler or handler is defined');
        }
        $return = [];

        // Conditions
        $return[ 0 ] = $this->conditionHandler;

        // Handler
        $self = $this;
        $return[ 1 ] = function(PortInterface $port) use (&$self) {
            $result = call_user_func($self->handler, $port, $self->matchedCondition, $self);

            if ($result and $this->logger) {
                $this->logger->debug(
                    "Found proxy id $result by \"Special Generator" . ($this->name ? " - {$this->name}" : '') .
                    "\" logic case for " . $port->getUserId() . " user",
                    ['condition' => $self->matchedCondition],
                    ['userId' => $port->getUserId()]
                );
            }

            return $result;
        };

        if (!is_null($this->fallbackDeterminator)) {
            $return[ 2 ] = $this->fallbackDeterminator;
        }

        return $return;
    }
}
