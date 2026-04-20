<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// BUG ensure every controller that has branchification in index() also saves branch_id correctly with the effectiveBranchId() global method

// [ ] add the order whose status is 'received' to inventoryStock with shelf as null so it would list out as pending for placing to shelf

class Shelf extends Model
{
    use HasFactory;

    protected $fillable = [
        'shelf_name',
        'shelf_type',
        'access_pin',
        'total_capacity_cm3',
        'category_restriction',
        'branch_id',
    ];

    protected $casts = [
        'total_capacity_cm3' => 'decimal:2',
        'is_sterile' => 'boolean',
    ];

    /**
     * Get the inventory items on this shelf.
     */
    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    /**
     * Get the used capacity on this shelf.
     */
    public function getUsedCapacityAttribute()
    {
        $total = 0;
        foreach ($this->inventoryStocks as $stock) {
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
