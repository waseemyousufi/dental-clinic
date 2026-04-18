<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $transactions = AccountTransaction::where('branch_id', $branchId)->get();
        return TransactionResource::collection($transactions);
    }

    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'transactionType' => 'required|string',
            'amount' => 'required|integer',
            'transactionDate' => 'required|date',
            'referenceType' => 'required|string',
            'description' => 'required|string',
            'recordedByEmployeeId' => 'required|integer',
            'accountId' => 'required|integer',
        ]);

        $transaction = AccountTransaction::create([
            'transaction_type' => $data['transactionType'],
            'amount' => $data['amount'],
            'transaction_date' => $data['transactionDate'],
            'reference_type' => $data['referenceType'],
            'description' => $data['description'],
            'recorded_by_employee_id' => $data['recordedByEmployeeId'],
            'account_id' => $data['accountId'],
            'branch_id' => $branchId,
        ]);

        Branch::find($transaction->branch_id)->accountTransactions()->save($transaction);

        $account = Account::find($data['accountId']);

        if ($data['transactionType'] == 'in') {
            $account->update([
                'total_amount' => $account->total_amount + $data['amount'],
            ]);
        } else if ($data['transactionType'] == 'out') {
            $account->update([
                'total_amount' => $account->total_amount - $data['amount'],
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'transactionType' => 'required|string',
            'amount' => 'required|integer',
            'transactionDate' => 'required|date',
            'referenceType' => 'required|string',
            'description' => 'required|string',
            'recordedByEmployeeId' => 'required|integer',
            'accountId' => 'required|integer',
        ]);

        return AccountTransaction::find($id)->update([
            'transaction_type' => $data['transactionType'],
            'amount' => $data['amount'],
            'transaction_date' => $data['transactionDate'],
            'reference_type' => $data['referenceType'],
            'description' => $data['description'],
            'recorded_by_employee_id' => $data['recordedByEmployeeId'],
            'account_id' => $data['accountId'],
            'branch_id' => $branchId,
        ]);

        $account = Account::find($data['accountId']);

        if ($data['transaction_type'] == 'in') {
            $account->update([
                'total_amount' => $account->total_amount + $data['amount'],
            ]);
        } else if ($data['transaction_type'] == 'out') {
            $account->update([
                'total_amount' => $account->total_amount - $data['amount'],
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // $transaction = AccountTransaction::find($id);
        // $account = Account::find($transaction->account_id);

        // if ($transaction->transaction_type == 'in') {
        //     $account->update([
        //         'total_amount' => $account->total_amount - $transaction->amount,
        //     ]);
        // } else if ($transaction->transaction_type == 'out') {
        //     $account->update([
        //         'total_amount' => $account->total_amount + $transaction->amount,
        //     ]);
        // }

        return AccountTransaction::delete($id);
    }
}
