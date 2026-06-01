<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Branch;
use Illuminate\Http\Request;

class AccountController extends Controller
{

    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        if($request->query('all')) {
            $accounts = Account::where('branch_id', $branchId)->get();
            return AccountResource::collection($accounts);
        }

        $accounts = Account::where('branch_id', $branchId)
            ->where('status', 'active')
            ->get();
        return AccountResource::collection($accounts);
    }

    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'accountName' => 'required|string',
            'accountType' => 'required|string',
            'status' => 'required|string',
        ]);

        Account::create([
            'account_name' => $data['accountName'],
            'account_type' => $data['accountType'],
            'total_amount' => (int) ($data['totalAmount'] ?? 0),
            'status' => $data['status'],
            'branch_id' => $branchId,
        ]);

        return response('created', 201);
    }

    public function update(Request $request, $id)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'accountName' => 'string',
            'accountType' => 'string',
            'totalAmount' => 'integer',
            'status' => 'string',
        ]);

        $account = Account::where('branch_id', $branchId)->findOrFail($id);

        $payload = [
            'branch_id' => $branchId,
        ];

        if (array_key_exists('accountName', $data)) {
            $payload['account_name'] = $data['accountName'];
        }

        if (array_key_exists('accountType', $data)) {
            $payload['account_type'] = $data['accountType'];
        }

        if (array_key_exists('totalAmount', $data)) {
            $payload['total_amount'] = $data['totalAmount'];
        }

        if (array_key_exists('status', $data)) {
            $payload['status'] = $data['status'];
        }

        $account->update($payload);
    }

    public function charge(Request $request, $id)
    {
        return $this->applyBalanceChange($request, $id, 'charge', 'account charge');
    }

    public function withdraw(Request $request, $id)
    {
        return $this->applyBalanceChange($request, $id, 'withdraw', 'account withdraw');
    }

    public function delete($id)
    {
        $account = Account::where('branch_id', $branchId)->findOrFail($id);

        if ((int) $account->total_amount > 0) {
            return response()->json(['message' => 'Accounts with credit cannot be deleted'], 422);
        }

        $incomeAccountCount = Account::query()
            ->where('branch_id', $account->branch_id)
            ->where('account_type', 'income')
            ->count();

        if ($account->account_type === 'income' && $incomeAccountCount === 1) {
            return response()->json(['message' => 'The only income account cannot be deleted'], 422);
        }

        $account->delete();
        return response()->json(['message' => 'deleted']);
    }

    // BUG account charge won't increase account total amount
    private function applyBalanceChange(Request $request, $id, string $transactionType, string $referenceType)
    {
        $branchId = $this->effectiveBranchId($request);
        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $account = Account::where('branch_id', $branchId)->findOrFail($id);
        $amount = (int) $data['amount'];
        $currentBalance = (int) $account->total_amount;

        if ($transactionType === 'withdraw' && $currentBalance < $amount) {
            return response()->json(['message' => 'Insufficient account balance'], 422);
        }

        $employeeId = $request->user()?->employee?->id ?? $request->user()?->id;

        $transaction = AccountTransaction::create([
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'transaction_date' => now()->toDateString(),
            'reference_type' => $referenceType,
            'description' => $data['description'] ?? ucfirst($referenceType),
            'recorded_by_employee_id' => $employeeId,
            'account_id' => $account->id,
            'branch_id' => $branchId,
        ]);

        Branch::find($branchId)?->accountTransactions()->save($transaction);

        $account->update([
            'total_amount' => $transactionType === 'charge'
                ? $currentBalance + $amount
                : $currentBalance - $amount,
        ]);

        return response()->json(['message' => 'ok'], 201);
    }
}
