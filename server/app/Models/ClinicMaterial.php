<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClinicMaterial extends Model
{

    public $timestamps = false;
protected $fillable = [
        'materials_name',
        'quantity',
        'amount',
        'total_amount',
        'expense_date',
        'description'
    ];

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function accountTransactions(): BelongsToMany
    {
        return $this->belongsToMany(AccountTransaction::class)->withTimestamps();
    }
}
