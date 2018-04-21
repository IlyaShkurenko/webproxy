<?php

namespace WHMCS\Module\Blazing\Export\Compiler;

class Compiler
{
    /**
     * @var array
     */
    private $fields;

    /**
     * Compiler constructor.
     *
     * @param $fields
     */
    public function __construct($fields = [])
    {
        $this->fields = $fields;
    }

    /**
     * @param CompileContext $context
     *
     * @return array
     */
    public function compile($context)
    {
        $result = [];
        /** @var CompileField $field */
        foreach ($this->fields as $field) {
            if (null === $field->getName()) {
                $result[] = $field->compile($context);
            } else {
                $result[$field->getName()] = $field->compile($context);
            }
        }
        return $result;
    }

}
