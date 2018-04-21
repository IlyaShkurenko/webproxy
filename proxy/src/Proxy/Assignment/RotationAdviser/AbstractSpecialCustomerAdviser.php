<?php

namespace Proxy\Assignment\RotationAdviser;

use Proxy\Assignment\Port\PortInterface;

abstract class AbstractSpecialCustomerAdviser extends AbstractParentDependentRotationAdviser
{
    public function isAbleToHandle(PortInterface $port)
    {
        foreach ($this->getHandlers() as $handler) {
            if (call_user_func($handler[0], $port)) {
                return true;
            }
        }

        return false;
    }

    public function shouldContinueIfFail(PortInterface $port)
    {
        $found = false;

        foreach ($this->getHandlers() as $handler) {
            if (call_user_func($handler[0], $port)) {
                $found = true;

                if (isset($handler[2])) {
                    if (is_callable($handler[2]) and !$handler[2]($port)) {
                        return false;
                    }
                    elseif (!$handler[2]) {
                        return false;
                    }
                }
                else {
                    return false;
                }
            }
        }

        return $found;
    }

    public function findRandomProxy(PortInterface $port)
    {
        foreach ($this->getHandlers() as $handler) {
            if (call_user_func($handler[0], $port)) {
                $result = call_user_func($handler[1], $port);

                if ($result) {
                    return $result;
                }
                // Fallback?
                if (!$result and isset($handler[2])) {
                    if (is_callable($handler[2]) and $handler[2]($port)) {
                        continue;
                    }
                    elseif ($handler[2]) {
                        continue;
                    }
                }

            }
        }

        return false;
    }

    /**
     * @return array in format [
     *  [ function() { check conditions }, function() { find proxy id }, bool_should_continue on fail ]
     * ]
     */
    abstract protected function getHandlers();
}
