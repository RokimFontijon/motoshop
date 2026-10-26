<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A spare part in the inventory. stock_qty is maintained by a database trigger. */
class Part extends Model
{
    // stock_qty is fillable so an admin can set the opening stock and receive deliveries.
    protected $fillable = ['part_code', 'name', 'unit_price', 'stock_qty', 'reorder_level'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2'];
    }

    public function jobOrderParts(): HasMany
    {
        return $this->hasMany(JobOrderPart::class);
    }

    /** Parts whose stock is at or below the reorder level (uses the partial index). */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_qty', '<=', 'reorder_level');
    }

    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->reorder_level;
    }
}
