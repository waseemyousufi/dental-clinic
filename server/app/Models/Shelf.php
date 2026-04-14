<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shelf extends Model
{
    use HasFactory;

    protected $fillable = [
        'shelf_name',
        'shelf_type',
        'access_pin',
        'total_capacity_cm3',
        'category_restriction',
    ];

    protected $casts = [
        'total_capacity_cm3' => 'decimal:2',
        'is_sterile' => 'boolean',
    ];

    /**
     * Get the inventory items on this shelf.
     */
    public function inventoryStock()
    {
        return $this->hasMany(InventoryStock::class);
    }

    /**
     * Get the used capacity on this shelf.
     */
    public function getUsedCapacityAttribute()
    {
        $total = 0;
        foreach ($this->inventoryStock as $stock) {
            $stockable = $stock->stockable;
            if ($stockable && $stockable->width && $stockable->height && $stockable->depth) {
                $total += ($stockable->width * $stockable->height * $stockable->depth * $stock->quantity);
            }
        }
        return $total;
    }

    /**
     * Get the available capacity on this shelf.
     */
    public function getAvailableCapacityAttribute()
    {
        return $this->total_capacity_cm3 - $this->used_capacity;
    }
}
