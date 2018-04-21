<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\ConfigBuilder\Field;

class TextareaFieldBuilder extends TextFieldBuilder
{
    const TYPE = 'textarea';
    public function setRowsCount($value)
    {
        $this->config['Rows'] = $value;
        return $this;
    }
    public function setColsCount($value)
    {
        $this->config['Rows'] = $value;
        return $this;
    }
}