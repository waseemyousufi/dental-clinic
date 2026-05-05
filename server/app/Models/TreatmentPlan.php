<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlan extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'procedure_id',
        'total_estimated_cost',
        'status',
        'duration',
        'total_amount_paid',
        'start_data'
    ];

    /**
     * Relationship to the Patient.
     * Note: Your migration has a unique constraint on patient_id.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relationship to the Appointment where this plan was proposed.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Relationship to the standardized Procedure from your catalog.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /**
     * Helper to check if the plan is ready for clinical execution.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }
}
