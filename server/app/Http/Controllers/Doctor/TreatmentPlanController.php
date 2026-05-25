<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\TreatmentPlanResource;
use App\Models\Appointment;
use App\Models\InventoryStock;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class TreatmentPlanController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $patientId = $request->query('patient_id');

        $query = TreatmentPlan::with(['procedure', 'appointments', 'patient'])
            ->where('branch_id', $branchId);

        if (is_numeric($patientId)) {
            $query->where('patient_id', (int) $patientId);
        }

        return TreatmentPlanResource::collection($query->latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'procedure_id' => 'required|exists:procedures,id',
            'total_estimated_cost' => 'required|integer|min:0',
            'status' => 'required|in:proposed,accepted,partially_accepted,rejected,completed',
            'appointments_needed' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
        ]);

        $branchId = $this->effectiveBranchId($request);

        DB::transaction(function () use ($branchId, $validated) {
        $plan = TreatmentPlan::create([
            ...$validated,
            'branch_id' => $branchId,
            'start_date' => $validated['start_date'] ?? now()->toDateString(),
        ]);

        $patient = Patient::where('branch_id', $branchId)->findOrFail($validated['patient_id']);
        $patient->update([
            'total_amount_due' => $patient->total_amount_due + $validated['total_estimated_cost'] ?? 0,
        ]);

        return response()->json(
            [
                'message' => 'Treatment plan created successfully',
                'data' => new TreatmentPlanResource($plan->load(['procedure', 'appointments'])),
            ],
            201
        );
        });
    }

    public function update(Request $request, string $id)
    {
        $plan = TreatmentPlan::where('branch_id', $branchId)->findOrFail($id);
        $branchId = $this->effectiveBranchId($request);

        if ((int) $plan->branch_id !== (int) $branchId) {
            return response()->json(['message' => 'Treatment plan not found in this branch'], 404);
        }

        DB::transaction(function () use ($branchId, $request, $id) {
        $validated = $request->validate([
            'patient_id' => 'sometimes|exists:patients,id',
            'procedure_id' => 'sometimes|exists:procedures,id',
            'total_estimated_cost' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:proposed,accepted,partially_accepted,rejected,completed',
            'appointments' => 'nullable|integer|min:0',
            'start_date' => 'sometimes|date',
        ]);

        // $patient = Patient::find($validated['patient_id']);
        // $patient->update([
        //     'total_amount_due' => $patient->total_amount_due + $validated['total_estimated_cost'] ?? 0,
        // ]);

        $plan = TreatmentPlan::where('branch_id', $branchId)->findOrFail($id);

        $plan->update($validated);
        });

        return response()->json([
            'message' => 'Treatment plan updated successfully',
            'data' => new TreatmentPlanResource($plan->fresh()->load(['procedure', 'appointments'])),
        ]);
    }

    public function addAppointment(Request $request, string $id)
    {
        $plan = TreatmentPlan::where('branch_id', $branchId)->findOrFail($id);
        $branchId = $this->effectiveBranchId($request);

        if ((int) $plan->branch_id !== (int) $branchId) {
            return response()->json(['message' => 'Treatment plan not found in this branch'], 404);
        }

        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
        ]);

        $appointment = Appointment::where('branch_id', $branchId)->findOrFail($validated['appointment_id']);
        $appointment->treatment_plan_id = $plan->id;
        $appointment->save();

        return response()->json([
            'message' => 'Appointment associated with treatment plan successfully',
            'data' => new TreatmentPlanResource($plan->fresh()->load(['procedure', 'appointments'])),
        ]);
    }

    public function execute(Request $request, $id)
    {
        $branchId = $this->effectiveBranchId($request);
        $plan = TreatmentPlan::where('branch_id', $branchId)
            ->with('procedure.inventoryRequirements')
            ->findOrFail($id);

        if ($plan->status !== 'accepted') {
            return response()->json(['error' => 'Only accepted plans can be executed.'], 400);
        }

        return DB::transaction(function () use ($plan, $request) {
            $treatment = Treatment::create([
                'treatment_type' => $plan->procedure->category,
                'diagnosis' => $request->diagnosis ?? 'Planned treatment executed',
                'treatment_date' => now(),
                'duration' => $request->duration ?? '00:30',
                'cost' => $plan->total_estimated_cost,
                'description' => $plan->procedure->name,
                'patient_id' => $plan->patient_id,
                'treatment_plan_id' => $plan->id,
                'branch_id' => $branchId,
            ]);

            foreach ($plan->procedure->inventoryRequirements as $requirement) {
                $stock = InventoryStock::where('branch_id', $plan->branch_id)
                    ->where('id', $requirement->inventory_stock_id)
                    ->where('quantity', '>=', $requirement->unit_count)
                    ->first();

                if ($stock) {
                    $stock->decrement('quantity', $requirement->unit_count);
                }
            }

            $plan->update(['status' => 'completed']);

            return response()->json([
                'message' => 'Treatment executed and inventory updated.',
                'treatment' => $treatment
            ]);
        });
    }

    public function updateStatus(Request $request, string $id)
    {
        $plan = TreatmentPlan::where('branch_id', $branchId)->findOrFail($id);
        $branchId = $this->effectiveBranchId($request);

        if ((int) $plan->branch_id !== (int) $branchId) {
            return response()->json(['message' => 'Treatment plan not found in this branch'], 404);
        }

        $request->validate(['status' => 'required|in:accepted,rejected,proposed,completed,partially_accepted']);
        $plan->update(['status' => $request->status]);

        return response()->json(['message' => 'Plan status updated to ' . $request->status]);
    }

    public function delete(Request $request, string $id)
    {
        $plan = TreatmentPlan::where('branch_id', $branchId)->findOrFail($id);
        $branchId = $this->effectiveBranchId($request);

        if ((int) $plan->branch_id !== (int) $branchId) {
            return response()->json(['message' => 'Treatment plan not found in this branch'], 404);
        }

        $plan->delete();

        return response()->json(['message' => 'Treatment plan deleted successfully']);
    }
}
