<?php

namespace Vendor\Jobby\Logger\Handler;

use Monolog\Handler\AbstractProcessingHandler;

class CallbackHandler extends AbstractProcessingHandler
{
    protected $callback;

    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record)
    {
        if (!$this->callback) {
            throw new \RuntimeException('Callback is not set');
        }

        call_user_func($this->callback, $record);
    }
}
