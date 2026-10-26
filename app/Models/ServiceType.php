<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A kind of maintenance work, for example "Change Oil". */
class ServiceType extends Model
{
    protected $fillable = ['name', 'description'];

    public function serviceIntervals(): HasMany
    {
        return $this->hasMany(ServiceInterval::class);
    }

    public function jobOrders(): BelongsToMany
    {
        return $this->belongsToMany(JobOrder::class, 'job_order_services', 'service_type_id', 'job_order_id');
    }
}
