<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'pricable_id',
        'pricable_type',
        'price',
        'effective_from',
        'discount_percentage',
        'currency_exchange_rate',
        'final_price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'effective_from' => 'datetime',
        'discount_percentage' => 'float',
        'currency_exchange_rate' => 'decimal:4',
        'final_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function pricable()
    {
        return $this->morphTo();
    }
}
