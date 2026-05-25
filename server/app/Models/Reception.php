<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reception extends Model
{

    public $timestamps = false;
    protected $fillable = [
        'branch_id',
        'status',
        'fee',
        'admission_timestamp',
        'employee_id',
        'patient_id'
    ];

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function Patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
