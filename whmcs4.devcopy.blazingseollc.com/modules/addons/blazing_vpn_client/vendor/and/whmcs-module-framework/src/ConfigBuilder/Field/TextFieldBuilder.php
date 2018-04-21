<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\ConfigBuilder\Field;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\ConfigBuilder\FieldConfigBuilder;
class TextFieldBuilder extends FieldConfigBuilder
{
    const TYPE = 'text';
    public function __construct()
    {
        $this->config['Type'] = static::TYPE;
        parent::__construct();
    }
}