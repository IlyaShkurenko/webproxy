<?php

namespace WHMCS\Module\Blazing\Export\Compiler;

/**
 * Class CallbackCompileField
 *
 * @package WMCS\Model\Blazing\Export\Compiler
 */
class CallbackCompileField implements CompileField
{

    /**
     * @var string|int|null
     */
    protected $name;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * CallbackCompileField constructor.
     *
     * @param          $name
     * @param callable $callback
     */
    public function __construct($name, callable $callback)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    /**
     * @inheritdoc
     */
    function getName()
    {
        return $this->name;
    }


    /**
     * @inheritdoc
     */
    function compile($context)
    {
        return call_user_func($this->callback, $context);
    }
}
