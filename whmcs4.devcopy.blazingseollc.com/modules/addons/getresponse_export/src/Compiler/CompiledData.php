<?php

namespace WHMCS\Module\Blazing\Export\Compiler;

interface CompiledData extends \IteratorAggregate
{
    /**
     * Retrieve compiled data.
     *
     * @return mixed
     */
    public function getRaw();

    /**
     * Retrieve rendered result.
     *
     * @return mixed
     */
    public function render();
}
