<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\ClinicExpense;
use Illuminate\Http\Request;

class ClinicExpenseController extends Controller
{

    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $expenses = ClinicExpense::where('branch_id', $branchId)->get();
        return $expenses;
    }

    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

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
            'branch_id' => $branchId,
        ]);
    }

    public function update(Request $request, $id)
    {
        $branchId = $this->effectiveBranchId($request);

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
            'branch_id' => $branchId,
        ]);

        return response('updated');
    }

    public function delete($id)
    {
        return ClinicExpense::delete($id);
    }
}
