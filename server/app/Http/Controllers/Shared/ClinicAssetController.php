<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\ClinicAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicAssetController extends Controller
{

    public function index()
    {
        $clinicAssets = Auth::user()->employee->Branch->ClinicAssets;
        return $clinicAssets;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'assetName' => 'required|string',
            'category' => 'required|string',
            'amount' => 'required|string',
            'price' => 'required|string',
            'totalAmount' => 'required|string',
            'dateOfPurchase' => 'required|string',
            'status' => 'required|string',
            'purchasedByEmployeeId' => 'required|integer',
        ]);

        return ClinicAsset::create([
            'asset_name' => $data['assetName'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'price' => $data['price'],
            'total_amount' => $data['totalAmount'],
            'date_of_purchase' => $data['dateOfPurchase'],
            'status' => $data['status'],
            'purchasedByEmployee_id' => $data['purchasedByEmployeeId'],
            'branch_id' => $request->user()->employee->branch_id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'assetName' => 'required|string',
            'category' => 'required|string',
            'amount' => 'required|string',
            'price' => 'required|string',
            'totalAmount' => 'required|string',
            'dateOfPurchase' => 'required|string',
            'status' => 'required|string',
            'purchasedByEmployeeId' => 'required|integer',
        ]);

        return ClinicAsset::find($id)->update([
            'asset_name' => $data['assetName'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'price' => $data['price'],
            'total_amount' => $data['totalAmount'],
            'date_of_purchase' => $data['dateOfPurchase'],
            'status' => $data['status'],
            'purchasedByEmployee_id' => $data['purchasedByEmployeeId'],
            'branch_id' => $request->user()->employee->branch_id,
        ]);
    }

    public function delete($id) 
    {
        return ClinicAsset::delete($id);
    }
}
