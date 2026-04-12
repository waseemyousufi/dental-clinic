<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicAsset extends Model
{

    public $timestamps = false;
protected $fillable = [
        'asset_name',
        'category',
        'amount',
        'price',
        'total_amount',
        'date_of_purchase',
        'status',
        'purchasedByEmployee_id',
        'branch_id'
    ];

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function Branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
