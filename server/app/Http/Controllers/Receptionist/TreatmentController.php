<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Treatment;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseFormatSame;

class TreatmentController extends Controller
{

    public function index()
    {
        $treatments = Auth::user()->employee->Branch->treatments;
        return $treatments;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'treatmentType' => 'required|string',
            'diagnosis' => 'required|string',
            'treatmentDate' => 'required|date',
            'duration' => 'required|string',
            'cost' => 'required|integer',
            'description' => 'required|string',
            'patientId' => 'required|integer',
            'xrayId' => 'required|integer',
        ]);

        Treatment::create([
            'treatment_type' => $data['treatmentType'],
            'diagnosis' => $data['diagnosis'],
            'treatment_date' => $data['treatmentDate'],
            'duration' => $data['duration'],
            'cost' => $data['cost'],
            'description' => $data['description'],
            'patient_id' => $data['patientId'],
            'xray_id' => $data['xrayId'],
            'branch_id' => $request->user()->employee->branch_id
        ]);

        return response('created', 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'treatmentType' => 'required|string',
            'diagnosis' => 'required|string',
            'treatmentDate' => 'required|date',
            'duration' => 'required|string',
            'cost' => 'required|integer',
            'description' => 'required|string',
            'patientId' => 'required|integer',
            'xrayId' => 'required|integer',
        ]);

        Treatment::find($id)->update([
            'treatment_type' => $data['treatmentType'],
            'diagnosis' => $data['diagnosis'],
            'treatment_date' => $data['treatmentDate'],
            'duration' => $data['duration'],
            'cost' => $data['cost'],
            'description' => $data['description'],
            'patient_id' => $data['patientId'],
            'xray_id' => $data['xrayId'],
            'branch_id' => $request->user()->employee->branch_id
        ]);

        return response('done');
    }

    public function delete($id)
    {
        Treatment::delete($id);
        return response('deleted');
    }
}
