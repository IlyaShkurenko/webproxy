<?php

namespace WHMCS\Module\Blazing\Export\GetResponse\Listener;

use WHMCS\Database\Capsule;
use WHMCS\Module\Blazing\Export\Vendor\WHMCS\Module\Framework\Events\AbstractModuleListener;

class Activated extends AbstractModuleListener
{

    protected $name = 'activate';

    /**
     * @param array
     *
     * @return mixed
     */
    protected function execute()
    {
        try {
            Capsule::schema()->create(
                'getresponseexport_exported_clients',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->increments('id');
                    $table->unsignedInteger('client_id');
                    $table->string('getresponse_id')->nullable();

                    // 0 - marks that user is not modified
                    // 1 - marks that user is updated and must be updated
                    $table->smallInteger('status')->default(0);

                    $table->timestamps();

                    $table->unique('client_id');
                }
            );
        } catch (\Exception $e) {
            echo "Unable to create getresponseexport_exported_clients: {$e->getMessage()}";
        }
    }
}
