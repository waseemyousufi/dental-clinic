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
        $data = $request->validate([
            'diagnosis' => 'required|string',
            'employeeId' => 'required|integer',
            'patientId' => 'required|integer',
            'appointmentDateId' => 'required|integer',
            'allergyId' => 'required|integer',
            'treatmentId' => 'required|integer',
        ]);

        $patient = Patient::find($data['patientId']);

        return $patient->PatientFile()->save([
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

        $data = $request->validate([
            'diagnosis' => 'required|string',
            'employeeId' => 'required|integer',
            'patientId' => 'required|integer',
            'appointmentDateId' => 'required|integer',
            'allergyId' => 'required|integer',
            'treatmentId' => 'required|integer',
        ]);

        $patient = Patient::find($data['patientId']);

        return $patient->PatientFile()->update([
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
        return Patient::find($id)->PatientFile->delete();
    }
}
