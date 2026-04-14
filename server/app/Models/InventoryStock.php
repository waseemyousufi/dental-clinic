<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryStock extends Model
{
    use HasFactory;

    protected $table = 'inventory_stock';

    protected $fillable = [
        'stockable_id',
        'stockable_type',
        'shelf_id',
        'quantity',
        'expiry_date',
        'batch_number',
        'status',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity' => 'integer',
    ];

    /**
     * Get the owning model (ClinicMaterial or ClinicAsset).
     */
    public function stockable()
    {
        return $this->morphTo();
    }

    /**
     * Get the shelf where this product is stored.
     */
    public function shelf()
    {
        return $this->belongsTo(Shelf::class);
    }

    /**
     * Scope for pending distribution items.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for placed items.
     */
    public function scopePlaced($query)
    {
        return $query->where('status', 'placed');
    }

    /**
     * Check if the product is expired.
     */
    public function getIsExpiredAttribute()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
