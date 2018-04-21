<?php
/**
 * @author Dmitriy Kachurovskiy <kd.softevol@gmail.com>
 */

namespace WHMCS\Module\Blazing\Export\GetResponse\Listener\Eloquent;

class ChangeListener
{

    private $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function table()
    {
        return $this->connection->table('getresponseexport_exported_clients');
    }

    public function markUpdated($id)
    {
        if ($id) {
            $q = is_array($id)
                ? $this->table()->where('client_id', $id)
                : $this->table()->whereIn('client_id', $id);
            $q->update(['status' => 1, 'updated_at' => date('Y-m-h H-i-s')]);
        }
    }

    public function clientUpdated($client)
    {
        $this->markUpdated($client->id);
    }

    public function transactionSaved($transaction)
    {
        $this->markUpdated($transaction->clientId);
    }

    public function serviceUpdated($service)
    {
        $this->markUpdated($service->clientId);
    }

    public function productUpdated($product)
    {
        $ids = $product->services()->pluck('userid')->all();
        $this->markUpdated($ids);
    }

    public function register()
    {
        \WHMCS\User\Client::updated([$this, 'clientUpdated']);
        \WHMCS\Product\Product::updated([$this, 'productUpdated']);
        \WHMCS\Service\Service::saved([$this, 'serviceUpdated']);
        \WHMCS\Billing\Payment\Transaction::saved([$this, 'transactionSaved']);
    }
}