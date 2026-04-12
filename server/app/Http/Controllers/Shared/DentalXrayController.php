<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\DentalXrayResource;
use App\Models\DentalXray;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DentalXrayController extends Controller
{

    public function index()
    {
        $xrays = Auth::user()->employee->Branch->DentalXray;
        return DentalXrayResource::collection($xrays);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "xray_type" => 'required|string',
            "xray_timestamp" => 'required|string',
            "tooth_part" => 'required|string',
            "side" => 'required|string',
            "image_path" => 'required|string',
            "diagnosis_notes" => 'required|string',
            "payment_status" => 'required|string',
            "results_summery" => 'required|string',
            "patient_id" => 'required',
            "requestedByEmployee_id" => 'required',
            "takenByEmployee_id" => 'required',
        ]);

        DentalXray::create([
            'xray_type' => $data['xray_type'],
            'xray_timestamp'  => $data['xray_timestamp'],
            'tooth_part' => $data['tooth_part'],
            'side' => $data['side'],
            'image_path'  => $data['image_path'],
            'diagnosis_notes' => $data['diagnosis_notes'],
            'payment_status' => $data['payment_status'],
            'results_summery' => $data['results_summery'],
            'patient_id' => $data['patient_id'],
            'requestedByEmployee_id' => $data['requestedByEmployee_id'],
            'takenByEmployee_id' => $data['takenByEmployee_id'],
            'branch_id' => $request->user()->employee->branch_id,
        ]);
    }

    public function update(Request $request, string $id) {
        
    }

    public function delete(string $id) {
        DentalXray::delete($id);
    }
}
