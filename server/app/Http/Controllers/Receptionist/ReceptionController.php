<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Reception;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{

    public function store(Request $request)
    {
        $data = $request->validate([
            'status' => 'string|required',
            'fee' => 'string|required',
            'createdAt' => 'string|required',
            'employeeId' => 'integer|required',
            'patientId' => 'integer|required',
        ]);

        Reception::create([
            'status' => $data['status'],
            'fee' => $data['fee'],
            'admission_timestamp' => $data['createdAt'],
            'employee_id' => $data['employeeId'],
            'patient_id' => $data['patientId'],
        ]);
    }
}
