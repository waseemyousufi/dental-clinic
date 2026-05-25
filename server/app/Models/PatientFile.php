<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PatientFile extends Model
{
    protected $fillable = ['branch_id', 'diagnosis', 'notes', 'odontogram_data', 'patient_id', 'employee_id', 'appointmentDate_id', 'allergy_id', 'treatment_id', 'diagnosis_notes'];
    public $timestamps = false;

    public function Patient() : BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function Employee() : BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function Appointment() : BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function Treatment() : BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function Allergy() : BelongsTo
    {
        return $this->belongsTo(Allergy::class);
    }
}
