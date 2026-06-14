<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureAppointment extends Model
{
    protected $table = 'procedure_appointment';
    protected $fillable = ['procedure_id', 'appointment_id'];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
