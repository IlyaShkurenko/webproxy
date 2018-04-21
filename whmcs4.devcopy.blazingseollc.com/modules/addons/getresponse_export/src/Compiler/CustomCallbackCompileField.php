<?php

namespace WHMCS\Module\Blazing\Export\Compiler;

class CustomCallbackCompileField extends CustomCompileField
{
    /**
     * @var callable
     */
    private $callback;

    public function __construct($id, callable $callback)
    {
        parent::__construct($id);
        $this->callback = $callback;
    }

    /**
     * Retrieve value for custom field. It may be array or scalar
     * which will be wrapped into array.
     *
     * @param $context
     *
     * @return mixed
     */
    function doCompile($context)
    {
        return call_user_func($this->callback, $context);
    }
}
