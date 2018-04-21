<?php

namespace WHMCS\Module\Blazing\Export\GetResponse\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $clientId
 * @property string $getresponseId
 * @property string $status
 * @property Carbon $createdAt
 * @property Carbon $updatedAt
 *
 * Class ExportedClient
 * @package WHMCS\Module\Blazing\Export\GetResponse\Model
 */
class ExportedClient extends Model
{
    const STATUS_ACTUAL = 00;
    const STATUS_CHANGED = 01;
    const STATUS_DECLINED = 02;
    const STATUS_UNSUBSCRIBED = 04;

    protected $table = 'getresponseexport_exported_clients';

    public function scopeChanged($query)
    {
        return $query->where('status', self::STATUS_CHANGED);
    }

    public function scopeSubscribed($query)
    {
        return $query->where('status', '!=', self::STATUS_UNSUBSCRIBED);
    }
}
