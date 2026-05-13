<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescription extends Model
{

    public $timestamps = false;
protected $fillable = [
    'drug_name',
    ];

    // public function Employee(): BelongsTo
    // {
    //     return $this->belongsTo(Employee::class);
    // }

    // public function Branch(): BelongsTo
    // {
    //     return $this->belongsTo(Branch::class);
    // }

    // public function Patient(): BelongsTo
    // {
    //     return $this->belongsTo(Patient::class);
    // }
}
