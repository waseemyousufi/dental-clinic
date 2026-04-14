<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_name',
        'date',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_name', 'organization_name');
    }
}
