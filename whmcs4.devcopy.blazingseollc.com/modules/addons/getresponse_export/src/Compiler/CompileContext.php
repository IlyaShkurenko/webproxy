<?php

namespace WHMCS\Module\Blazing\Export\Compiler;

/**
 * Interface CompileContext
 *
 * Context which provides data for building fields.
 *
 * @package WHMCS\Module\Blazing\Export\Compiler
 */
interface CompileContext
{
    /**
     * Retrieve config provided for context.
     *
     * @param null $key
     *
     * @return array|null|string
     */
    public function getConfig($key = null);
}
