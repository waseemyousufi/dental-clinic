<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{

    public $timestamps = false;
protected $fillable = ['appointment_timestamp', 'status', 'description', 'branch_id'];

    public function Branch() : BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'appointment_patient', 'appointment_id', 'patient_id')->withTimestamps();
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'appointment_employee', 'appointment_id', 'employee_id')->withTimestamps();
    }

    public function PatientFile() : HasOne
    {
        return $this->hasOne(PatientFile::class);
    }
}
