<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/** A staff account (admin or mechanic) that can log in. */
class User extends Authenticatable
{
    // Mass assignment: role_id is allowed here, but the controllers decide
    // which role a person gets (public registration is always "mechanic").
    protected $fillable = ['name', 'email', 'password', 'role_id'];

    // Never include these when a user is converted to an array / JSON.
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            // "hashed" means: whatever is assigned to password is hashed automatically.
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Job orders assigned to this user as the mechanic. */
    public function jobOrders(): HasMany
    {
        return $this->hasMany(JobOrder::class, 'mechanic_id');
    }

    public function isAdmin(): bool
    {
        return $this->role->name === 'admin';
    }
}
