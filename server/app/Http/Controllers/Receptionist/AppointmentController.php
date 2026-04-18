<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Patient;
use Illuminate\Http\Request;
use App\Http\Resources\AppointmentResource;

class AppointmentController extends Controller
{

    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $appointments = Appointment::where('branch_id', $branchId)->get();
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
            'description' => 'string',
            'status' => 'string',
            'employeeId' => 'required',
            'patientId' => 'required'
        ]);

        $appointment = Appointment::create([
            'appointment_timestamp' => $data['appointment_timestamp'],
            'description' => $data['description'],
            'status' => $data['status'],
            'branch_id' => $branchId,
        ]);

        $patient = Patient::find($data['patientId']);
        $appointment->patients()->syncWithoutDetaching([$patient->id]);

        $employee = Employee::find($data['employeeId']);
        $appointment->employees()->syncWithoutDetaching([$employee->id]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'appointment_timestamp' => 'required',
            'description' => 'string',
            'status' => 'string',
            'employeeId' => 'integer|required',
            'patientId' => 'integer|required'
        ]);

        Appointment::where('id', $id)->update([
            'appointment_timestamp' => $data['appointment_timestamp'],
            'description' => $data['description'],
            'status' => $data['status'],
            'branch_id' => $branchId
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
