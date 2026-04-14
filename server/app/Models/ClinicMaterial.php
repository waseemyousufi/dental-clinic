<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClinicMaterial extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
        'category',
        'width',
        'height',
        'depth',
        'is_sterile',
        'material_name',
        'quantity',
        'amount',
        'total_amount',
        'expense_date',
    ];

    protected $casts = [
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'depth' => 'decimal:2',
        'is_sterile' => 'boolean',
    ];

    /**
     * Get the prices for this clinic material.
     */
    public function prices()
    {
        return $this->morphMany(ProductPrice::class, 'pricable');
    }

    /**
     * Get the active price for this clinic material.
     */
    public function activePrice()
    {
        return $this->morphOne(ProductPrice::class, 'pricable')->where('is_active', true)->latest('effective_from');
    }

    /**
     * Get the inventory stock for this clinic material.
     */
    public function inventoryStock()
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

    /**
     * Calculate the volume of a single clinic material in cm³.
     */
    public function getVolumeAttribute()
    {
        if ($this->width && $this->height && $this->depth) {
            return $this->width * $this->height * $this->depth;
        }
        return 0;
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function accountTransactions(): BelongsToMany
    {
        return $this->belongsToMany(AccountTransaction::class)->withTimestamps();
    }
}
