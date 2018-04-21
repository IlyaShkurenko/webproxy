<?php

namespace WHMCS\Module\Blazing\Export\Compiler;

interface CompileField
{
    /**
     * Retrieve name of the field which.
     * Null may represent key field as only array value.
     *
     * @return string|int|null
     */
    public function getName();

    /**
     * Compile fields data into proper representation.
     *
     * @param CompileContext $context
     *
     * @return mixed
     */
    public function compile($context);
}
