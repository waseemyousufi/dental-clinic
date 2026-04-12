<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $patients = Auth::user()->employee->Branch->Patient;
        return PatientResource::collection($patients);
    }

    public function show(string $id)
    {
        return new PatientResource(Patient::findOrFail($id));
    }

    public function setAllergy(Request $request)
    {
        $data = $request->validate([
            'allergyType' => 'string|required',
            'severity' => 'string|required',
            'description' => 'string|required',
            'patientId' => 'integer|required',
        ]);

        $patient = Patient::find($data['patientId']);

        return $patient->allergy->save([
            'allergy_type' => $data['allergyType'],
            'severity' => $data['severity'],
            'description' => $data['description'],
            'branch_id' => $request->user()->employee->branch_id,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'fName' => 'required|string',
            'lName' => 'required|string',
            'gender' => 'required|string',
            'bloodType' => 'required|string',
            'emgContact' => 'required|string',
            'registerationDate' => 'required|string',
            'phone' => 'required|string',
        ]);

        return Patient::create([
            'f_name' => $data['fName'],
            'l_name' => $data['lName'],
            'gender' => $data['gender'],
            'phone' => $data['phone'],
            'blood_type' => $data['bloodType'],
            'emergency_contact' => $data['emgContact'],
            'registeration_date' => $data['registerationDate'],
            'branch_id' => $request->user()->employee->branch_id,
        ]);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'fName' => 'required|string',
            'lName' => 'required|string',
            'gender' => 'required|string',
            'bloodType' => 'required|string',
            'emgContact' => 'required|string',
            'registerationDate' => 'required|string',
            'phone' => 'required|string',
        ]);

        return Patient::find($id)->update([
            'f_name' => $data['fName'],
            'l_name' => $data['lName'],
            'gender' => $data['gender'],
            'phone' => $data['phone'],
            'blood_type' => $data['bloodType'],
            'emergency_contact' => $data['emgContact'],
            'registeration_date' => $data['registerationDate'],
            'branch_id' => $request->user()->employee->branch_id,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return Patient::delete($id);
    }
}
