<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A person who owns one or more motorcycles. */
class Customer extends Model
{
    protected $fillable = ['full_name', 'phone', 'address'];

    public function motorcycles(): HasMany
    {
        return $this->hasMany(Motorcycle::class);
    }
}
