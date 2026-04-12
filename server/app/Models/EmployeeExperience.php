<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeExperience extends Model
{

    public $timestamps = false;
protected $fillable = [
        'workplace',
        'position',
        'total_amount',
        'employee_id'
    ];

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
