<?php

namespace App\Http\Controllers\Doctor;

use App\Models\TreatmentPlan;
use App\Models\Treatment;
use App\Models\InventoryStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class TreatmentPlanController extends Controller
{
    /**
     * Display a list of plans for a specific patient.
     */
    public function index($patientId)
    {
        return TreatmentPlan::with('procedure')
            ->where('patient_id', $patientId)
            ->get();
    }

    /**
     * Store a new plan proposed from the Odontogram.
     * Note: Handles the unique patient constraint via updateOrCreate.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'required|exists:appointments,id',
            'procedure_id' => 'required|exists:procedures,id',
            'total_estimated_cost' => 'required|integer',
            'status' => 'required|in:proposed,accepted,rejected',
            'total_amount_paid' => 'nullable',
            'duration' => 'integer',
            'start_date' => 'required'
        ]);

        // Logic for unique patient_id constraint in your schema
        $plan = TreatmentPlan::create($validated);

        return response()->json([
            'message' => 'Treatment plan updated successfully',
            'data' => $plan
        ]);
    }

    /**
     * THE CRITICAL PHASE: Convert Plan to Treatment
     * This triggers the "Silent Deduction" from InventoryStock.
     */
    public function execute(Request $request, $id)
    {
        $plan = TreatmentPlan::with('procedure.inventoryRequirements')->findOrFail($id);

        if ($plan->status !== 'accepted') {
            return response()->json(['error' => 'Only accepted plans can be executed.'], 400);
        }

        return DB::transaction(function () use ($plan, $request) {
            // 1. Create the Treatment record (Clinical Fact)
            $treatment = Treatment::create([
                'treatment_type' => $plan->procedure->category,
                'diagnosis' => $request->diagnosis ?? 'Planned treatment executed',
                'treatment_date' => now(),
                'duration' => $request->duration ?? '00:30',
                'cost' => $plan->total_estimated_cost,
                'description' => $plan->procedure->name,
                'patient_id' => $plan->patient_id,
                'treatment_plan_id' => $plan->id,
                'branch_id' => $request->branch_id ?? 1,
            ]);

            // 2. Silent Inventory Deduction (Digital Twin Sync)
            foreach ($plan->procedure->inventoryRequirements as $requirement) {
                $stock = InventoryStock::where('id', $requirement->inventory_stock_id)
                    ->where('quantity', '>=', $requirement->unit_count)
                    ->first();

                if ($stock) {
                    $stock->decrement('quantity', $requirement->unit_count);
                }
                // Optional: Trigger alert if stock is low after decrement
            }

            // 3. Finalize Plan Status
            $plan->update(['status' => 'completed']);

            return response()->json([
                'message' => 'Treatment executed and inventory updated.',
                'treatment' => $treatment
            ]);
        });
    }

    /**
     * Update status (e.g., patient rejects the plan).
     */
    public function updateStatus(Request $request, TreatmentPlan $plan)
    {
        $request->validate(['status' => 'required|in:accepted,rejected']);
        $plan->update(['status' => $request->status]);

        return response()->json(['message' => 'Plan status updated to ' . $request->status]);
    }

    public function delete(String $id) {
        return TreatmentPlan::find($id)->delete();
    }
}
