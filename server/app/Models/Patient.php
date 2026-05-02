<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    public $timestamps = false;
    protected $fillable = ['f_name', 'l_name', 'gender', 'emergency_contact', 'allergies', 'phone', 'blood_type', 'registeration_date', 'branch_id'];



    public function appointments()
    {
        return $this->belongsToMany(
            Appointment::class,
            'appointment_patient',
            'patient_id',
            'appointment_id'
        )->withTimestamps();
    }

    public function DentalXray(): HasOne
    {
        return $this->hasOne(DentalXray::class);
    }

    public function Branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function PatientFile(): HasOne
    {
        return $this->hasOne(PatientFile::class);
    }

    public function Prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    public function Reception(): HasOne
    {
        return $this->hasOne(Reception::class);
    }

    public function Treatment(): HasOne
    {
        return $this->hasOne(Treatment::class);
    }

    public function Allergy(): HasMany
    {
        return $this->hasMany(Allergy::class);
    }
}
