<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ToothCondition extends Model
{
    use HasUuids; // Ensures Laravel handles the UUID primary key

    protected $fillable = [
        'patient_id',
        'tooth_id',
        'condition_id',
        'surfaces',
        'drawing_data',
        'notes',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     * This automatically converts JSON strings from the DB into PHP arrays.
     */
    protected $casts = [
        'surfaces' => 'array',
        'drawing_data' => 'array',
        'is_active' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function tooth()
    {
        return $this->belongsTo(TeethReference::class, 'tooth_id');
    }

    public function conditionLibrary()
    {
        return $this->belongsTo(ConditionLibrary::class, 'condition_id');
    }
}
