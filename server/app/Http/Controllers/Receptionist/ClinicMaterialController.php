<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClinicMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CliniMaterialController extends Controller
{

    public function index() {
        $clinicMaterial = Auth::user()->employee->Branch->ClinicMaterial;
        return $clinicMaterial;
    }

    public function show($id) {
        return ClinicMaterial::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'materialsName' => 'required|string',
            'quantity' => 'required|string',
            'amount' => 'required|string',
            'totalAmount' => 'required|string',
            'expenseDate' => 'required|string',
            'description' => 'required|string'
        ]);

        ClinicMaterial::create([
            'materials_name' => $data['materialsName'],
            'quantity' => $data['quantity'],
            'amount' => $data['amount'],
            'total_amount' => $data['totalAmount'],
            'expense_date' => $data['expenseDate'],
            'description' => $data['description'],
        ]);

        return response('created', 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'materialsName' => 'required|string',
            'quantity' => 'required|string',
            'amount' => 'required|string',
            'totalAmount' => 'required|string',
            'expenseDate' => 'required|string',
            'description' => 'required|string'
        ]);

        ClinicMaterial::find($id)->update([
            'materials_name' => $data['materialsName'],
            'quantity' => $data['quantity'],
            'amount' => $data['amount'],
            'total_amount' => $data['totalAmount'],
            'expense_date' => $data['expenseDate'],
            'description' => $data['description'],
        ]);

        return response('created', 201);
    }

    public function delete($id)
    {
        return ClinicMaterial::delete($id);
    }
}
