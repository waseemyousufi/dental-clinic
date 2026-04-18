<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Models\Account;
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
            'totalAmount' => 'required|integer',
            'status' => 'required|string',
        ]);

        Account::create([
            'account_name' => $data['accountName'],
            'account_type' => $data['accountType'],
            'total_amount' => $data['totalAmount'],
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

        Account::find($id)->update([
            'account_name' => $data['accountName'],
            'account_type' => $data['accountType'],
            'total_amount' => $data['totalAmount'],
            'status' => $data['status'],
            'branch_id' => $branchId,
        ]);
    }

    public function delete($id)
    {
        Account::delete($id);
        return 'deleted';
    }
}
