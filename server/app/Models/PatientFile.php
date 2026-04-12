<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PatientFile extends Model
{


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
