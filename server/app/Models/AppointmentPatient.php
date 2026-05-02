<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentPatient extends Model
{
    use HasFactory;

    protected $table = 'appointment_patient';

    // Pivot tables usually don't need incrementing ID
    public $incrementing = false;

    // If your table doesn't have a primary key
    protected $primaryKey = null;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'patient_id',
        'appointment_id',
    ];

    /**
     * Relationships
     */

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
