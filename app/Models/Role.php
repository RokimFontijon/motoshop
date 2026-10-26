<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A user role: "admin" or "mechanic". */
class Role extends Model
{
    // Mass assignment: only these columns can be filled with Role::create([...]).
    protected $fillable = ['name', 'description'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
