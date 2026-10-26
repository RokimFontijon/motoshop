<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One visit of a motorcycle to the shop. The center of the whole system. */
class JobOrder extends Model
{
    public const STATUSES = ['pending', 'ongoing', 'completed', 'released'];

    protected $fillable = [
        'job_number', 'motorcycle_id', 'mechanic_id', 'service_date',
        'odometer_at_service', 'complaint', 'status', 'labor_cost',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'labor_cost' => 'decimal:2',
        ];
    }

    public function motorcycle(): BelongsTo
    {
        return $this->belongsTo(Motorcycle::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    /** The services done in this job order (many-to-many through job_order_services). */
    public function serviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(ServiceType::class, 'job_order_services', 'job_order_id', 'service_type_id');
    }

    /** The spare part lines used in this job order. */
    public function partLines(): HasMany
    {
        return $this->hasMany(JobOrderPart::class);
    }

    /** Work that is finished. Only finished visits count as history for due dates. */
    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereIn('status', ['completed', 'released']);
    }

    /**
     * Who may change this job order?
     *   admin    : always
     *   mechanic : only their own job order, and not after it was released
     * The database repeats this rule (row level security), see database/sql/01_db_objects.sql.
     */
    public function canBeEditedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->mechanic_id === $user->id && $this->status !== 'released';
    }

    public function partsTotal(): float
    {
        return (float) $this->partLines->sum(fn ($line) => $line->quantity * $line->unit_price);
    }

    public function grandTotal(): float
    {
        return (float) $this->labor_cost + $this->partsTotal();
    }
}
