<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Treatment extends Model
{

    public $timestamps = false;
protected $fillable = [
        'treatment_type',
        'diagnosis',
        'treatment_date',
        'duration',
        'cost',
        'description',
        'patient_id',
        'xray_id',
    ];

    public function Branch() : BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)->withTimestamps();
    }

    public function PatientFile(): HasOne
    {
        return $this->hasOne(PatientFile::class);
    }

    public function Patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function DentalXray(): BelongsTo
    {
        return $this->belongsTo(DentalXray::class);
    }
}
