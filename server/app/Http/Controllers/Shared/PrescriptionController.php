<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrescriptionController extends Controller
{

    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $prescriptions = Prescription::where('branch_id', $branchId)->get();
        return response()->json(['data' => $prescriptions]);
    }

    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $data  = $request->validate([
            'name' => 'required|string',
            // 'prescriptionDate' => 'required|string',
            // 'instructions' => 'required|string',
            // 'patientId' => 'required|integer',
        ]);

        $employee = $request->user()->employee;

        $prescription = Prescription::create([
            // 'prescription_date' => $data['prescriptionDate'],
            // 'instructions' => $data['instructions'],
            // 'employee_id' => $employee->id,
            // 'patient_id' => $data['patientId'],
            'branch_id' => $branchId,
            'drug_name' => $data['name']
        ]);

        return response()->json(['data' => $prescription], 201);
    }

    public function update(Request $request, string $id)
    {
        $branchId = $this->effectiveBranchId($request);

        $data  = $request->validate([
            // 'prescriptionDate' => 'required|string',
            // 'instructions' => 'required|string',
            // 'patientId' => 'required|integer',
            'name' => 'required|string'
        ]);

        $prescription = Prescription::where('branch_id', $branchId)->findOrFail($id);
        $prescription->update([
            // 'prescription_date' => $data['prescriptionDate'],
            // 'instructions' => $data['instructions'],
            // 'employee_id' => $employee->id,
            // 'patient_id' => $data['patientId'],
            'branch_id' => $branchId,
            'drug_name' => $data['name']
        ]);

        return response()->json(['data' => $prescription]);
    }

    public function destroy(Request $request, string $id)
    {
        $branchId = $this->effectiveBranchId($request);
        $prescription = Prescription::where('branch_id', $branchId)->findOrFail($id);

        if (!$prescription) {
            return response()->json(['message' => 'Prescription not found'], 404);
        }

        $prescription->delete();

        return response()->json(['message' => 'Prescription deleted successfully'], 200);
    }
}
