<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\ClinicExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicExpenseController extends Controller
{

    public function index()
    {
        $expenses = Auth::user()->employee->Branch->ClinicExpense;
        return $expenses;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expenseCategory' => 'string|required',
            'unit'  => 'string|required',
            'amount'  => 'string|required',
            'expenseDate'  => 'string|required',
            'description'  => 'string|required',
            'paidByEmployeeId'  => 'string|required',
        ]);

        ClinicExpense::create([
            'expense_category' => $data['expenseCategory'],
            'unit' => $data['unit'],
            'amount' => $data['amount'],
            'expense_date' => $data['expenseDate'],
            'description' => $data['description'],
            'paidByEmployee_id' => $data['paidByEmployeeId'],
            'branch_id' => $request->user()->employee->branch_id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'expenseCategory' => 'string|required',
            'unit'  => 'string|required',
            'amount'  => 'string|required',
            'expenseDate'  => 'string|required',
            'description'  => 'string|required',
            'paidByEmployeeId'  => 'string|required',
        ]);

        ClinicExpense::find($id)->update([
            'expense_category' => $data['expenseCategory'],
            'unit' => $data['unit'],
            'amount' => $data['amount'],
            'expense_date' => $data['expenseDate'],
            'description' => $data['description'],
            'paidByEmployee_id' => $data['paidByEmployeeId'],
            'branch_id' => $request->user()->employee->branch_id,
        ]);

        return response('updated');
    }

    public function delete($id)
    {
        return ClinicExpense::delete($id);
    }
}
