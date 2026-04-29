<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{


    public $timestamps = false;
    protected $fillable = [
        'f_name',
        'l_name',
        'gender',
        'hire_date',
        'qualification',
        'speciality',
        'medical_license_number',
        'work_start_time',
        'work_end_time',
        'position_id',
        'branch_id',
        'user_id'
    ];

    public function casts(): array
    {
        return [
            'gender' => Gender::class,
        ];
    }

    public function AccountTransaction(): HasMany
    {
        return  $this->hasMany(AccountTransaction::class);
    }

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class)->withTimestamps();
    }

    public function ClinicAsset(): HasOne
    {
        return $this->hasOne(ClinicAsset::class);
    }

    public function ClinicExpense(): HasOne
    {
        return $this->hasOne(ClinicExpense::class);
    }

    public function RequestedDentalXray(): HasOne
    {
        return $this->hasOne(DentalXray::class);
    }

    public function TakenDentalXray(): HasOne
    {
        return $this->hasOne(DentalXray::class);
    }

    public function Position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function Branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class)->withTimestamps();
    }

    public function experience(): HasOne
    {
        return $this->hasOne(EmployeeExperience::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    public function PatientFile(): HasMany
    {
        return $this->hasMany(PatientFile::class);
    }

    public function Prescription(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function Reception(): HasMany
    {
        return $this->hasMany(Reception::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
