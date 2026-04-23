<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeethReference extends Model
{
    protected $table = 'teeth_reference';

    // We only need to READ from this usually, but keep it fillable for seeding
    protected $fillable = ['fdi_code', 'universal_code', 'type', 'quadrant', 'default_position'];

    /**
     * Relationship: All conditions ever recorded for this tooth.
     */
    public function toothConditions()
    {
        return $this->hasMany(ToothCondition::class, 'tooth_id');
    }

    /**
     * Relationship: The current visual state of the tooth.
     */
    public function activeConditions()
    {
        // This links the static tooth to the specific findings
        return $this->hasMany(ToothCondition::class, 'tooth_id');
    }
}
