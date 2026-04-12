<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\DentalXrayResource;
use App\Models\DentalXray;
use Illuminate\Http\Request;

class XrayController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'xrayType' => 'require|string',
            'createdAt' => 'required|string',
            'toothPart' => 'required|string',
            'side' => 'required|string',
            'diagnosisNotes' => 'required|string',
            'paymentStatus' => 'required|string',
            'resultsSummery' => 'required|string',
            'imagePath' => 'required|string',
            'patientId' => 'required|integer',
            'requestedByEmployeeId' => 'required|integer',
            'takenByEmloyeeId' => 'required|integer',
        ]);

        DentalXray::create([
            'xray_type' => $data['xrayType'],
            'xray_timestamp' => $data['createdAt'],
            'tooth_part' => $data['toothPart'],
            'side' => $data['side'],
            'image_path' => $data['imagePath'],
            'diagnosis_notes' => $data['diagnosisNotes'],
            'payment_status' => $data['paymentStatus'],
            'results_summery' => $data['resultsSummery'],
            'patient_id' => $data['patientId'],
            'requestedByEmployee_id' => $data['requestedByEmployeeId'],
            'takenByEmloyee_id' => $data['takenByEmployeeId'],
            'branch_id' => $request->user()->employee->branch_id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'xrayType' => 'require|string',
            'createdAt' => 'required|string',
            'toothPart' => 'required|string',
            'side' => 'required|string',
            'diagnosisNotes' => 'required|string',
            'paymentStatus' => 'required|string',
            'resultsSummery' => 'required|string',
            'imagePath' => 'required|string',
            'patientId' => 'required|integer',
            'requestedByEmployeeId' => 'required|integer',
            'takenByEmloyeeId' => 'required|integer',
        ]);

        DentalXray::find($id)->update([
            'xray_type' => $data['xrayType'],
            'xray_timestamp' => $data['createdAt'],
            'tooth_part' => $data['toothPart'],
            'side' => $data['side'],
            'image_path' => $data['imagePath'],
            'diagnosis_notes' => $data['diagnosisNotes'],
            'payment_status' => $data['paymentStatus'],
            'results_summery' => $data['resultsSummery'],
            'patient_id' => $data['patientId'],
            'requestedByEmployee_id' => $data['requestedByEmployeeId'],
            'takenByEmloyee_id' => $data['takenByEmployeeId'],
            'branch_id' => $request->user()->employee->branch_id,
        ]);
    }
}
