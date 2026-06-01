<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\ClinicExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ClinicExpenseController extends Controller
{

    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $expenses = ClinicExpense::where('branch_id', $branchId)->get();
        return ExpenseResource::collection($expenses);
    }


    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $employeeId = $request->user()->employee->id;
        // return $employeeId;

        $data = $request->validate([
            'expenseCategory' => 'string|required',
            'unit'  => 'string|required',
            'amount'  => 'integer|required',
            'expenseDate'  => 'string|required',
            'description'  => 'string|nullable',
            // 'paidByEmployeeId'  => 'required',
            'accountId' => 'required'
        ]);

        DB::transaction(function () use ($branchId, $data, $employeeId) {
            ClinicExpense::create([
                'expense_category' => $data['expenseCategory'],
                'unit' => $data['unit'],
                'amount' => $data['amount'],
                'expense_date' => $data['expenseDate'],
                'description' => $data['description'],
                'paidByEmployee_id' => $employeeId,
                'branch_id' => $branchId,
            ]);

            AccountTransaction::create([
                'transaction_type' => 'out',
                'amount' => $data['amount'],
                'transaction_date' => Carbon::now(),
                'reference_type' => 'expense',
                'description' => $data['description'],
                'recorded_by_employee_id' => $employeeId,
                'account_id' => $data['accountId'],
                'branch_id' => $branchId,
            ]);

            $acc = Account::find($data['accountId']);
            $acc->total_amount = $acc->total_amount - $data['amount'];
            $acc->save();
        });
    }

    public function update(Request $request, $id)
    {
        $branchId = $this->effectiveBranchId($request);
        $employeeId = $request->user()->employee->id;

        $data = $request->validate([
            'expenseCategory' => 'string|required',
            'unit'  => 'string|required',
            'amount'  => 'string|required',
            'expenseDate'  => 'string|required',
            'description'  => 'string|required',
            // 'paidByEmployeeId'  => 'string|required',
        ]);

        ClinicExpense::where('branch_id', $branchId)->findOrFail($id)->update([
            'expense_category' => $data['expenseCategory'],
            'unit' => $data['unit'],
            'amount' => $data['amount'],
            'expense_date' => $data['expenseDate'],
            'description' => $data['description'],
            'paidByEmployee_id' => $employeeId,
            'branch_id' => $branchId,
        ]);

        return response('updated');
    }

    public function delete($id)
    {
        $branchId = $this->effectiveBranchId(request());
        return ClinicExpense::where('branch_id', $branchId)->findOrFail($id)->delete();
    }
}
