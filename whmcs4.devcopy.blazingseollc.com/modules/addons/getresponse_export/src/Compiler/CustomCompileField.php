<?php

namespace WHMCS\Module\Blazing\Export\Compiler;

abstract class CustomCompileField implements CompileField
{
    /**
     * @var string
     */
    private $id;

    /**
     * CustomCompiledField constructor.
     *
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return null;
    }

    /**
     * Retrieve value for custom field. It may be array or scalar
     * which will be wrapped into array.
     *
     * @param $context
     *
     * @return mixed
     */
    abstract function doCompile($context);

    /**
     * Compile fields data into proper representation.
     *
     * @param CompileContext $context
     *
     * @return mixed
     */
    public function compile($context)
    {
        $value = $this->doCompile($context);
        if (!is_array($value)) {
            $value = [$value];
        }
        return [
            'customFieldId' => $this->id,
            'value'         => $value
        ];
    }
}
