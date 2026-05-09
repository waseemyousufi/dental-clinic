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
        return 'hi';

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
            'status' => 'string',
            'employeeId' => 'required',
            'patientId' => 'required',
            'treatment_plan_id' => 'nullable|exists:treatment_plans,id',
            'appointment_cost' => 'required|numeric',
            'clinical_notes' => 'nullable|string',
        ]);

        $account = Account::where('branch_id', $branchId)->where('account_type', 'income')->first();

        DB::transaction(function () use ($branchId, $data, $request, $account) {
        $appointment = Appointment::create([
            'appointment_timestamp' => $data['appointment_timestamp'],
            'description' => $data['description'],
            'status' => $data['status'],
            'treatment_plan_id' => $data['treatment_plan_id'] ?? null,
            'branch_id' => $branchId,
            'appointment_cost' => $data['appointment_cost'],
            'clinical_notes' => $data['clinical_notes'],
        ]);

        if($data['appointment_cost'] > 0) {
            AccountTransaction::create([
                'transaction_type' => 'in',
                'amount' => $data['appointment_cost'],
                'transaction_date' => $data['appointment_timestamp'],
                'reference_type' => 'appointment',
                'description' => 'Appointment cost',
                'recorded_by_employee_id' => $request->user()->id ?? null,
                'account_id' => 1,
                'branch_id' => $branchId,
            ]);

            Account::where('branch_id', $branchId)->where('account_type', 'income')->first()->update([
                'total_amount' => $account->total_amount + $data['appointment_cost'],
            ]);
        }

        $patient = Patient::find($data['patientId']);
        $appointment->patients()->syncWithoutDetaching([$patient->id]);

        $employee = Employee::find($data['employeeId']);
        $appointment->employees()->syncWithoutDetaching([$employee->id]);
    });
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, String $id)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'appointment_timestamp' => 'required',
            'description' => 'string',
            'status' => 'string',
            'employeeId' => 'integer|required',
            'patientId' => 'integer|required',
            'treatment_plan_id' => 'nullable|exists:treatment_plans,id',
            'appointment_cost' => 'required|numeric',
            'clinical_notes' => 'string',
        ]);

        Appointment::where('id', $id)->update([
            'appointment_timestamp' => $data['appointment_timestamp'],
            'description' => $data['description'],
            'status' => $data['status'],
            'treatment_plan_id' => $data['treatment_plan_id'] ?? null,
            'branch_id' => $branchId,
            'appointment_cost' => $data['appointment_cost'],
            'clinical_notes' => $data['clinical_notes'],
        ]);

        $appointment = Appointment::find($id);

        $employee = Employee::find($data['employeeId']);
        $appointment->employees()->sync([$employee->id]);

        $patient = Patient::find($data['patientId']);
        $appointment->patients()->sync([$patient->id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        Appointment::find($id)->delete();
    }
}
