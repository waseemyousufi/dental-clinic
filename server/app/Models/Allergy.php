<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Allergy extends Model
{
    public $timestamps = false;
protected $fillable = [
        'allergy_type',
        'severity',
        'description',
        'branch_id',
        'patient_id'
    ];

    public function Patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function Branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function PatientFile()
    {
        return $this->hasOne(PatientFile::class);
    }
}
