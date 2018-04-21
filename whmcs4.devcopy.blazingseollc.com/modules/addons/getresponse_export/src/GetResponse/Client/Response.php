<?php

namespace WHMCS\Module\Blazing\Export\GetResponse\Client;

/**
 * Class Response
 *
 * @package WHMCS\Module\Blazing\Export\GetResponse\Client
 */
class Response implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var string
     */
    private $status;

    /**
     * @var string|null
     */
    private $message;

    /**
     * @var string|null
     */
    private $code;

    /**
     * @var string|null
     */
    private $codeDescription;

    /**
     * @var array|null
     */
    private $result;

    /**
     * Response constructor.
     *
     * @param  int        $status
     * @param string|null $message
     * @param int|null    $errorCode
     * @param string|null $codeDescription
     * @param array|null  $result
     */
    public function __construct(
        $status,
        $message = null,
        $errorCode = null,
        $codeDescription = null,
        $result = []
    ) {
        $this->status = $status;
        $this->message = $message;
        $this->code = $errorCode;
        $this->codeDescription = $codeDescription;
        $this->result = $result;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->status >= 200 && $this->status <= 300;
    }

    /**
     * @return bool
     */
    public function isFatal()
    {
        return $this->status === 400;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getCodeDescription()
    {
        return $this->codeDescription;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->result[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->result[$offset] : null;
    }

    /**
     * @return bool
     */
    public function isNotFound()
    {
        return $this->getStatus() === 404;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException(
            'Result container cannot be modified.'
        );
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException(
            'Result container cannot be modified.'
        );
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->result);
    }
}
