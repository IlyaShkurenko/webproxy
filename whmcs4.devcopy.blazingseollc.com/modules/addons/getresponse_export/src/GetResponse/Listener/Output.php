<?php
/**
 * @author Dmitriy Kachurovskiy <kd.softevol@gmail.com>
 */

namespace WHMCS\Module\Blazing\Export\GetResponse\Listener;

use WHMCS\Module\Blazing\Export\Vendor\WHMCS\Module\Framework\Events\AbstractModuleListener;
use WHMCS\Module\Blazing\Export\GetResponse\Client\Client;

class Output extends AbstractModuleListener
{

    protected $name = 'output';

    /**
     * @param array
     *
     * @return mixed
     */
    protected function execute()
    {
        $module = $this->getModule();
        $apiKey = $module->getConfig('apiKey');
        $client = new Client($apiKey);
        $campaigns = $client->getCampaigns();
        echo '<table class="table table-striped">
                <thead>
                    <tr><td>Campaign name</td><td>Campaign id</td></tr>
                </thead>
             <tbody>';
        foreach ($campaigns as $campaign) {
            echo "<tr><td>{$campaign['name']}</td><td>{$campaign['campaignId']}</td></tr>";
        }
        echo '</tbody></table>';
    }
}
