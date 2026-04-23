<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\ToothCondition;
use App\Models\TeethReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ToothResource;

class OdontogramController extends Controller
{
    /**
     * Get the full state of a patient's mouth.
     */
public function show($patientId)
{
    // Fetch EVERY tooth from the reference table.
    // Constrain the relationship so we ONLY see findings for THIS patient.
    $teeth = TeethReference::with(['activeConditions' => function ($query) use ($patientId) {
        $query->where('patient_id', $patientId);
    }, 'activeConditions.conditionLibrary'])->get();

    // Check: if $teeth->count() is less than 52, your DB is missing teeth!
    return ToothResource::collection($teeth);
}


    /**
     * Save a new finding or treatment.
     */
// ToothConditionController.php

public function store(Request $request, $patientId)
{
    // ... validation logic ...

    $condition = ToothCondition::create([
        'patient_id'           => $patientId,
        'tooth_id'             => $request->tooth_id,
        'condition_id' => $request->condition_id,
        'surfaces'             => $request->surfaces,
        'drawing_data'         => $request->drawing_data ?? [],
        'is_active'            => true
    ]);

    // LOAD THE RELATIONSHIP HERE
    $condition->load('conditionLibrary');

    return response()->json([
        'success' => true,
        'data'    => $condition
    ], 201);
}
}
