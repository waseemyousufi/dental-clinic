<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\ToothCondition;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class ToothConditionController extends Controller
{
    /**
     * Store a new tooth condition.
     * Triggered when a user clicks a tooth surface with a tool selected.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id'   => 'required|exists:patients,id',
            'tooth_id'     => 'required|exists:teeth_reference,id',
            'condition_id' => 'required|exists:condition_library,id',
            'surfaces'     => 'required|array',
            'drawing_data' => 'nullable|array'
        ]);

        // Create the record
        $condition = ToothCondition::create([
            'patient_id'           => $validated['patient_id'],
            'tooth_id'             => $validated['tooth_id'],
            'condition_library_id' => $validated['condition_id'],
            'surfaces'             => $validated['surfaces'],
            'drawing_data'         => $validated['drawing_data'] ?? [],
            'is_active'            => true
        ]);

        // Load the relationship so the frontend gets the ui_color immediately
        $condition->load('conditionLibrary');

        return response()->json([
            'success' => true,
            'data'    => $condition
        ], 201);
    }

    /**
     * Delete a specific condition by ID.
     * Triggered when a user clicks an already colored surface to reset it.
     */
    public function destroy($id): JsonResponse
    {
        $condition = ToothCondition::find($id);

        if (!$condition) {
            return response()->json([
                'success' => false,
                'message' => 'Condition record not found'
            ], 404);
        }

        $condition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Condition removed successfully'
        ]);
    }
}
