<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalary extends Model
{

    public $primaryKey = 'employee_id';
    public $incrementing = false;
    protected $fillable = [
        'salary_month',
        'amount',
        'bonus',
        'total_amount',
        'remark',
        'paidByAccountTransaction_id',
        'employee_id'
    ];

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function AccountTransaction(): BelongsTo
    {
        return $this->belongsTo(AccountTransaction::class, 'paidByAccountTransaction_id');
    }
}
