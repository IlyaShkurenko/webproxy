<?php

namespace WHMCS\Module\Blazing\Export\Compiler;

/**
 * Class CompositeCompileField
 *
 * @package WHMCS\Module\Blazing\Export\Compiler
 */
class ArrayCompileField implements CompileField
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $fields;

    /**
     * CompositeCompileField constructor.
     *
     * @param       $name
     * @param array $fields
     */
    public function __construct($name, array $fields)
    {
        $this->name = $name;
        $this->fields = $fields;
    }

    /**
     * @return string
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * @param CompileContext $context
     *
     * @return array
     */
    function compile($context)
    {
        $result = [];
        /** @var CompileField $field */
        foreach ($this->fields as $field) {
            $val = $field->compile($context);
            // needs to be replaced with filters on custom fields
            if (!isset($val['value'][0]) || !$val['value'][0] && !is_numeric($val['value'][0])) {
                continue;
            }
            if (null === $field->getName()) {
                $result[] = $val;
            } else {
                $result[$field->getName()] = $val;
            }
        }
        return $result;
    }
}
