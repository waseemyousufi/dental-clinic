<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DentalXray extends Model
{

    public $timestamps = false;
protected $fillable = [
        'xray_type',
        'xray_timestamp',
        'tooth_part',
        'side',
        'image_path',
        'diagnosis_notes',
        'payment_status',
        'results_summery',
        'patient_id',
        'requestedByEmployee_id',
        'takenByEmployee_id',
        'branch_id'
    ];

    public function Patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function RequestedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, "requestedByEmployee_id");
    }

    public function TakenByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, "takenByEmployee_id");
    }

    public function Branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function Treatment(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }
}
