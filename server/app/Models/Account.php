<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    public $timestamps = false;
protected $fillable = [
        'account_name',
        'account_type',
        'total_amount',
        'status',
        'branch_id'
    ];

    public function Branch(): BelongsTo
    {
        return $this->BelongsTo(Branch::class);
    }

    public function AccounTransaction(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }
}
