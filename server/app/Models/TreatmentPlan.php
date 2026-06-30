<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentPlan extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'patient_id',
        'procedure_id',
        'branch_id',
        'createdBy_id',
        'total_estimated_cost',
        'status',
        'appointments_needed',
        'total_amount_paid',
        'start_date',
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
     * Relationship to the Employee who created this plan.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'createdBy_id');
    }

    /**
     * Relationship to the Appointment where this plan was proposed.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
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
