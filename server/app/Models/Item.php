<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'materials',
        'description',
        'track_stock',
        'requires_batch',
        'requires_expiry',
        'is_consumable',
    ];

    protected $casts = [
        'materials' => 'array',
        'track_stock' => 'boolean',
        'requires_batch' => 'boolean',
        'requires_expiry' => 'boolean',
        'is_consumable' => 'boolean',
    ];

    /**
     * Get the suppliers that provide this item.
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_item')->withTimestamps();
    }

    /**
     * Get the prices for this item.
     */
    public function prices(): MorphMany
    {
        return $this->morphMany(ProductPrice::class, 'pricable');
    }

    /**
     * Get the active price for this item.
     */
    public function activePrice()
    {
        return $this->morphOne(ProductPrice::class, 'pricable')->where('is_active', true)->latest('effective_from');
    }

    /**
     * Get the inventory stock for this item.
     */
    public function inventoryStock(): MorphMany
    {
        return $this->morphMany(InventoryStock::class, 'stockable');
    }

    /**
     * Get the total quantity in stock across all locations.
     */
    public function getTotalQuantityAttribute()
    {
        return $this->inventoryStock()->sum('quantity');
    }
}
