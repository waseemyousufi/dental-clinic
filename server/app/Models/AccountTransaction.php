<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AccountTransaction extends Model
{
    public $timestamps = false;
protected $fillable = [
        'transaction_type',
        'amount',
        'transaction_date',
        'reference_type',
        'description',
        'recorded_by_employee_id',
        'account_id',
        'branch_id',
    ];

    public $table = "account_transactions";

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recorded_by_employee_id');
    }

    public function Account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function Branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function clinicMaterials(): BelongsToMany
    {
        return $this->belongsToMany(ClinicMaterial::class)->withTimestamps();
    }

    public function EmployeeSalary(): HasOne
    {
        return $this->hasOne(EmployeeSalary::class);
    }
}
