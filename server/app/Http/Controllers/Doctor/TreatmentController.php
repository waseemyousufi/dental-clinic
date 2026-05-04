<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Treatment;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseFormatSame;

class TreatmentController extends Controller
{

    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $treatments = Treatment::where('branch_id', $branchId)->get();
        return $treatments;
    }

    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'treatmentType' => 'required|string',
            'diagnosis' => 'required|string',
            'treatmentDate' => 'required|date',
            'duration' => 'required|string',
            'cost' => 'required|integer',
            'description' => 'required|string',
            'patientId' => 'required|integer',
            'treatmentPlan_id' => 'required',
            // 'xrayId' => 'required|integer',
        ]);

        Treatment::create([
            'treatment_type' => $data['treatmentType'],
            'diagnosis' => $data['diagnosis'],
            'treatment_date' => $data['treatmentDate'],
            'duration' => $data['duration'],
            'cost' => $data['cost'],
            'description' => $data['description'],
            'patient_id' => $data['patientId'],
            'treatment_plan_id' => $data['treatmentPlan_id'],
            // 'xray_id' => $data['xrayId'],
            'branch_id' => $branchId
        ]);

        return response('created', 201);
    }

    public function update(Request $request, $id)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'treatmentType' => 'required|string',
            'diagnosis' => 'required|string',
            'treatmentDate' => 'required|date',
            'duration' => 'required|string',
            'cost' => 'required|integer',
            'description' => 'required|string',
            'patientId' => 'required|integer',
            'treatmentPlan_id' => 'required',
            // 'xrayId' => 'required|integer',
        ]);

        Treatment::find($id)->update([
            'treatment_type' => $data['treatmentType'],
            'diagnosis' => $data['diagnosis'],
            'treatment_date' => $data['treatmentDate'],
            'duration' => $data['duration'],
            'cost' => $data['cost'],
            'description' => $data['description'],
            'patient_id' => $data['patientId'],
            'treatment_plan_id' => $data['treatmentPlan_id'],
            // 'xray_id' => $data['xrayId'],
            'branch_id' => $branchId
        ]);

        return response('done');
    }

    public function delete($id)
    {
        Treatment::delete($id);
        return response('deleted');
    }
}
