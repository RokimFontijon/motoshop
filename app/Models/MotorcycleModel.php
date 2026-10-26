<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A model in the catalog, for example "Honda Click 125". */
class MotorcycleModel extends Model
{
    protected $fillable = ['brand', 'name', 'engine_cc'];

    public function motorcycles(): HasMany
    {
        return $this->hasMany(Motorcycle::class);
    }

    /** The service intervals (km / months) the manufacturer recommends for this model. */
    public function serviceIntervals(): HasMany
    {
        return $this->hasMany(ServiceInterval::class);
    }

    /** "Honda Click 125" */
    public function label(): string
    {
        return $this->brand.' '.$this->name;
    }
}
