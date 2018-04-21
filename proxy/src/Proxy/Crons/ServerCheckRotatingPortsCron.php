<?php

namespace Proxy\Crons;

/**
 * Class ServerCheckRotatingPorts based on "rotateCheck.php"
 *
 * @package Reseller\Crons
 */
class ServerCheckRotatingPortsCron extends AbstractDefaultSettingsCron
{

    protected $config = [
        'schedule' => '*/5 * * * *',
        'enabled'  => false
    ];

    protected $settings = [
        'servers'   => [
            // '108.174.48.170',
            '205.234.153.91'
        ],
        'url' => 'www.bing.com',
        'portStart' => 1025,
        'portEnd'   => 1029
    ];

    public function run()
    {
        $errors = 0;
        $returns = [];

        foreach ($this->getSetting('servers') as $ip) {
            for ($port = $this->getSetting('portStart'); $port <= $this->getSetting('portEnd'); $port++) {
                $ch = curl_init($this->getSetting('url'));
                curl_setopt($ch, CURLOPT_PROXY, $ip . ":" . $port);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
                $e = curl_exec($ch);

                $returns[$port] = curl_getinfo($ch);
                $error = curl_error($ch);
                if ($error) {
                    $errors++;
                    $this->output("$ip:$port is not reached");
                } else {
                    $this->output("$ip:$port connection is fine");
                }
            }

            if ($errors > 1) {
                $this->alertEmail('More Than One Rotate Proxy Showed up with Problems', print_r($returns, true));
            }
        }

        return true;
    }
}
