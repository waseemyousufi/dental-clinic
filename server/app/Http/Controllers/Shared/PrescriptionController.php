<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrescriptionController extends Controller
{

    public function index()
    {
        $prescriptions = Auth::user()->employee->Branch->Prescription;
        return $prescriptions;
    }

    public function store(Request $request)
    {
        $data  = $request->validate([
            'prescriptionDate' => 'required|string',
            'instructions' => 'required|string',
            'patientId' => 'required|integer',
        ]);

        $employee = $request->user()->employee;

        return Prescription::create([
            'prescription_date' => $data['prescriptionDate'],
            'instructions' => $data['instructions'],
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'patient_id' => $data['patientId'],
        ]);
    }

    public function update(Request $request,string $id)
    {
        $data  = $request->validate([
            'prescriptionDate' => 'required|string',
            'instructions' => 'required|string',
            'patientId' => 'required|integer',
        ]);

        $employee = $request->user()->employee;

        return Prescription::find($id)->update([
            'prescription_date' => $data['prescriptionDate'],
            'instructions' => $data['instructions'],
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'patient_id' => $data['patientId'],
        ]);
    }
}
