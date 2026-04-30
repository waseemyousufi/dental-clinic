<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_person_name',
        'organization_name',
        'phone',
        'email',
        'status',
        'business_id',
        'branch_id',
        'address',
        'notes'
    ];

    /**
     * Get the orders placed with this supplier.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class); // Assumes supplier_id
    }

    /**
     * Get the items this supplier provides.
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'supplier_item')->withTimestamps();
    }
}
