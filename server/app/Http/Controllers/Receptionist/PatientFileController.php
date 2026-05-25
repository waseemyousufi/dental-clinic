<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientFileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $data = $request->validate([
            'diagnosis' => 'required|string',
            'employeeId' => 'required|integer',
            'patientId' => 'required|integer',
            'appointmentDateId' => 'required|integer',
            'allergyId' => 'required|integer',
            'treatmentId' => 'required|integer',
        ]);

        $patient = Patient::where('branch_id', $branchId)->findOrFail($data['patientId']);

        return $patient->PatientFile()->create([
            'branch_id' => $branchId,
            'diagnosis' => $data['diagnosis'],
            'employee_id' => $data['employeeId'],
            'appointmentDate_id' => $data['appointmentDateId'],
            'allergy_id' => $data['allergyId'],
            'treatment_id' => $data['treatmentId'],
            'diagnosis_notes' => $patient->DentalXray->diagnosis_notes,
        ]);
    }

    public function update(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'diagnosis' => 'required|string',
            'employeeId' => 'required|integer',
            'patientId' => 'required|integer',
            'appointmentDateId' => 'required|integer',
            'allergyId' => 'required|integer',
            'treatmentId' => 'required|integer',
        ]);

        $patient = Patient::where('branch_id', $branchId)->findOrFail($data['patientId']);

        return $patient->PatientFile()->update([
            'branch_id' => $branchId,
            'diagnosis' => $data['diagnosis'],
            'employee_id' => $data['employeeId'],
            'appointmentDate_id' => $data['appointmentDateId'],
            'allergy_id' => $data['allergyId'],
            'treatment_id' => $data['treatmentId'],
            'diagnosis_notes' => $patient->DentalXray->diagnosis_notes,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $branchId = $this->effectiveBranchId(request());
        return Patient::where('branch_id', $branchId)->findOrFail($id)->PatientFile->delete();
    }
}
