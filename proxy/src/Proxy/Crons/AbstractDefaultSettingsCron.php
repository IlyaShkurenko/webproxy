<?php

namespace Proxy\Crons;

abstract class AbstractDefaultSettingsCron extends AbstractCron
{
    private $_defaultSettings = [
        'alertEmail' => 'michael@splicertech.com, admin@blazingseollc.com, ag.softevol@gmail.com, and.webdev@gmail.com',
        'alertFrom' => 'info@blazingseollc.com',
        'pwPort' => 4444
    ];

    protected function loadSettings()
    {
        return $this->getMergedDataWithClassConfig(parent::loadSettings(), $this->_defaultSettings, __CLASS__);
    }

    protected function prepareClassDataClassConfig(array $config)
    {
        return ($this->getDiffDataWithClassConfig(
            parent::prepareClassDataClassConfig($config), __CLASS__, function (array $config) {
                return ['settings' => $config];
        }));
    }

    protected function alertEmail($subject, $message, $from = '')
    {
        if (!$from) {
            $from = $this->getSetting('alertFrom');
        }

        $result = mail($this->getSetting('alertEmail'), $subject, $message, "From: $from");
        $this->output("email sent \"$subject\", result - \"$result\"");
    }
}
