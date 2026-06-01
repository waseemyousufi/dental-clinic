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

        Branch::where('clinic_owner_id', auth()->user()?->clinic_owner_id)
            ->findOrFail($transaction->branch_id)
            ->accountTransactions()
            ->save($transaction);

        $account = Account::where('branch_id', $branchId)->findOrFail($data['accountId']);

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

        $transaction = AccountTransaction::where('branch_id', $branchId)->findOrFail($id);
        $transaction->update([
            'transaction_type' => $data['transactionType'],
            'amount' => $data['amount'],
            'transaction_date' => $data['transactionDate'],
            'reference_type' => $data['referenceType'],
            'description' => $data['description'],
            'recorded_by_employee_id' => $data['recordedByEmployeeId'],
            'account_id' => $data['accountId'],
            'branch_id' => $branchId,
        ]);

        $account = Account::where('branch_id', $branchId)->findOrFail($data['accountId']);

        if ($data['transactionType'] == 'in') {
            $account->update([
                'total_amount' => $account->total_amount + $data['amount'],
            ]);
        } else if ($data['transactionType'] == 'out') {
            $account->update([
                'total_amount' => $account->total_amount - $data['amount'],
            ]);
        }

        return response()->json(['message' => 'Transaction updated successfully', 'transaction' => new TransactionResource($transaction)]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $transaction = AccountTransaction::where('branch_id', $this->effectiveBranchId(request()))->findOrFail($id);
        $account = Account::where('branch_id', $transaction->branch_id)->findOrFail($transaction->account_id);

        if ($transaction->transaction_type == 'in' || $transaction->transaction_type == 'charge') {
            $account->update([
                'total_amount' => $account->total_amount - $transaction->amount,
            ]);
        } else if ($transaction->transaction_type == 'out' || $transaction->transaction_type == 'withdraw') {
            $account->update([
                'total_amount' => $account->total_amount + $transaction->amount,
            ]);
        }

        $transaction->transaction_type = 'voided';
        $transaction->save();
        return response()->json(['message' => 'Transaction voided successfully', 'transaction' => new TransactionResource($transaction)]);
    }
}
