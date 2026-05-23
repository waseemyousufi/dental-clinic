<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Patient;
use Illuminate\Http\Request;
use App\Http\Resources\AppointmentResource;
use App\Models\Account;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{

    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $employeeId = $request->user()?->employee?->id;

        $query = Appointment::where('branch_id', $branchId);

        $appointments = $query->get();
        return AppointmentResource::collection($appointments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'appointment_timestamp' => 'required',
            'description' => 'nullable|string',
            'status' => 'required|string',
            'employeeId' => 'required|integer',
            'patientId' => 'required|integer',
            'treatment_plan_id' => 'nullable|exists:treatment_plans,id',
            'appointment_cost' => 'required|numeric',
            'clinical_notes' => 'nullable|string',

            // frontend still sends single value
            'procedure_id' => 'required|exists:procedures,id',
        ]);

        DB::transaction(function () use ($branchId, $data, $request) {

            $appointment = Appointment::create([
                'appointment_timestamp' => $data['appointment_timestamp'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'treatment_plan_id' => $data['treatment_plan_id'] ?? null,
                'branch_id' => $branchId,
                'appointment_cost' => $data['appointment_cost'],
                'clinical_notes' => $data['clinical_notes'] ?? null,
                'procedure_id' => $data['procedure_id'],
            ]);

            // ✅ M:M compatibility layer (IMPORTANT PART)
            $appointment->procedures()->sync([$data['procedure_id']]);

            $patient = Patient::findOrFail($data['patientId']);
            // if ($data['status'] === 'Completed') {
            //     $patient->total_amount_due += $data['appointment_cost'];
            //     $patient->save();

            //     $account = Account::where('branch_id', $branchId)->where('account_type', 'income')->first();
            //     $account->update([
            //         'total_amount' => $account->total_amount + $data['appointment_cost'],
            //         'branch_id' => $branchId
            //     ]);

            //     AccountTransaction::create([
            //         'account_id' => $account->id,
            //         'amount' => $data['appointment_cost'],
            //         'type' => 'debit',
            //         'description' => "Charge for appointment #{$appointment->id}",
            //         'branch_id' => $branchId,
            //     ]);
            // }
            $appointment->patients()->sync([$patient->id]);

            $employee = Employee::findOrFail($data['employeeId']);
            $appointment->employees()->sync([$employee->id]);
        });

        return response()->json([
            'message' => 'Appointment created successfully'
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'appointment_timestamp' => 'required',
            'description' => 'nullable|string',
            'status' => 'required|string',
            'employeeId' => 'required|integer',
            'patientId' => 'required|integer',
            'treatment_plan_id' => 'nullable|exists:treatment_plans,id',
            'appointment_cost' => 'required|numeric',
            'clinical_notes' => 'nullable|string',
            'procedure_id' => 'required|exists:procedures,id',
        ]);

        $appointment = Appointment::findOrFail($id);

        DB::transaction(function () use ($appointment, $data, $branchId) {
            $appointment->update([
                'appointment_timestamp' => $data['appointment_timestamp'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'treatment_plan_id' => $data['treatment_plan_id'] ?? null,
                'branch_id' => $branchId,
                'appointment_cost' => $data['appointment_cost'],
                'clinical_notes' => $data['clinical_notes'] ?? null,
                'procedure_id' => $data['procedure_id'],
            ]);

            if ($data['status'] === 'completed') {
                $patient = Patient::findOrFail($data['patientId']);
                $patient->total_amount_due += $data['appointment_cost'];
                $patient->save();

                // $account = Account::where('branch_id', $branchId)->where('account_type', 'income')->first();
                // $account->update([
                //     'total_amount' => $account->total_amount + $data['appointment_cost'],
                //     'branch_id' => $branchId
                // ]);

                // AccountTransaction::create([
                //     'account_id' => $account->id,
                //     'amount' => $data['appointment_cost'],
                //     'type' => 'debit',
                //     'description' => "Charge for appointment #{$appointment->id}",
                //     'branch_id' => $branchId,
                // ]);
            }



            $appointment->procedures()->sync([$data['procedure_id']]);

            $appointment->employees()->sync([$data['employeeId']]);
            $appointment->patients()->sync([$data['patientId']]);

            return response()->json([
                'message' => 'Appointment updated successfully',
                'appointment' => $appointment->fresh()
            ]);
        });
    }
    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        Appointment::find($id)->delete();
    }
}
