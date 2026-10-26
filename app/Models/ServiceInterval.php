<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** "Model X needs service Y every N km or M months" (a row of the many-to-many table). */
class ServiceInterval extends Model
{
    protected $fillable = ['motorcycle_model_id', 'service_type_id', 'interval_km', 'interval_months'];

    public function motorcycleModel(): BelongsTo
    {
        return $this->belongsTo(MotorcycleModel::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
}
