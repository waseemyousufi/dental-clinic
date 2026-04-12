<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicExpense extends Model
{
    public $timestamps = false;
protected $fillable = [
        'expense_category',
        'unit',
        'amount',
        'expense_date',
        'description',
        'paidByEmployee_id',
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
