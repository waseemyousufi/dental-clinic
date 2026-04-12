<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{

    public function index(Request $request)
    {
        if($request->query('all')) {
            $accounts = Account::where('branch_id', $request->user()->employee->branch_id)->get();
            return AccountResource::collection($accounts);
        }

        $user = Auth::user();
        $accounts = $user->employee
            ->branch
            ->Account()
            ->where('status', 'active')
            ->get();
        return AccountResource::collection($accounts);
    }

    public function store(Request $request)
    {
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
            'branch_id' => $request->user()->employee->branch_id,
        ]);

        return response('created', 201);
    }

    public function update(Request $request, $id)
    {
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
            'branch_id' => $request->user()->employee->branch_id,
        ]);
    }

    public function delete($id)
    {
        Account::delete($id);
        return 'deleted';
    }
}
