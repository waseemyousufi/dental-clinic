<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClinicAssetResource;
use App\Models\ClinicAsset;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicAssetController extends Controller
{
    public function index()
    {
        $branch = Auth::user()->employee->Branch;
        $clinicAssets = $branch->ClinicAssets()
            ->with(['Employee', 'Branch', 'activePrice', 'inventoryStock.shelf'])
            ->get();
        return ClinicAssetResource::collection($clinicAssets);
    }

    public function show($id)
    {
        $clinicAsset = ClinicAsset::with(['Employee', 'Branch', 'activePrice', 'prices', 'inventoryStock.shelf'])
            ->findOrFail($id);
        return new ClinicAssetResource($clinicAsset);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'assetName' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:device,furniture',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'isSterile' => 'boolean',
            'amount' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'totalAmount' => 'required|numeric|min:0',
            'dateOfPurchase' => 'required|date',
            'status' => 'required|string|in:active,inactive,maintenance',
            'purchasedByEmployeeId' => 'nullable|integer|exists:employees,id',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            'currencyExchangeRate' => 'nullable|numeric|min:0',
        ]);

        $clinicAsset = ClinicAsset::create([
            'name' => $data['name'],
            'asset_name' => $data['assetName'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'depth' => $data['depth'] ?? null,
            'is_sterile' => $data['isSterile'] ?? false,
            'amount' => $data['amount'],
            'price' => $data['price'],
            'total_amount' => $data['totalAmount'],
            'date_of_purchase' => $data['dateOfPurchase'],
            'status' => $data['status'],
            'purchasedByEmployee_id' => $data['purchasedByEmployeeId'] ?? Auth::user()->employee->id,
            'branch_id' => Auth::user()->employee->branch_id,
        ]);

        // Create price record
        ProductPrice::create([
            'pricable_id' => $clinicAsset->id,
            'pricable_type' => ClinicAsset::class,
            'price' => $data['price'],
            'effective_from' => now(),
            'is_active' => true,
            'discount_percentage' => $data['discountPercentage'] ?? null,
            'currency_exchange_rate' => $data['currencyExchangeRate'] ?? null,
        ]);

        return new ClinicAssetResource($clinicAsset->load(['Employee', 'Branch', 'activePrice']));
    }

    public function update(Request $request, $id)
    {
        $clinicAsset = ClinicAsset::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'assetName' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|string|in:device,furniture',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'isSterile' => 'boolean',
            'amount' => 'sometimes|integer|min:0',
            'price' => 'sometimes|numeric|min:0',
            'totalAmount' => 'sometimes|numeric|min:0',
            'dateOfPurchase' => 'sometimes|date',
            'status' => 'sometimes|string|in:active,inactive,maintenance',
            'purchasedByEmployeeId' => 'nullable|integer|exists:employees,id',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            'currencyExchangeRate' => 'nullable|numeric|min:0',
        ]);

        $clinicAsset->update([
            'name' => $data['name'] ?? $clinicAsset->name,
            'asset_name' => $data['assetName'] ?? $clinicAsset->asset_name,
            'description' => $data['description'] ?? $clinicAsset->description,
            'category' => $data['category'] ?? $clinicAsset->category,
            'width' => $data['width'] ?? $clinicAsset->width,
            'height' => $data['height'] ?? $clinicAsset->height,
            'depth' => $data['depth'] ?? $clinicAsset->depth,
            'is_sterile' => $data['isSterile'] ?? $clinicAsset->is_sterile,
            'amount' => $data['amount'] ?? $clinicAsset->amount,
            'price' => $data['price'] ?? $clinicAsset->price,
            'total_amount' => $data['totalAmount'] ?? $clinicAsset->total_amount,
            'date_of_purchase' => $data['dateOfPurchase'] ?? $clinicAsset->date_of_purchase,
            'status' => $data['status'] ?? $clinicAsset->status,
            'purchasedByEmployee_id' => $data['purchasedByEmployeeId'] ?? $clinicAsset->purchasedByEmployee_id,
            'branch_id' => Auth::user()->employee->branch_id,
        ]);

        // Update price if provided
        if (isset($data['price'])) {
            $activePrice = $clinicAsset->activePrice;
            if ($activePrice) {
                $activePrice->update([
                    'price' => $data['price'],
                    'discount_percentage' => $data['discountPercentage'] ?? $activePrice->discount_percentage,
                    'currency_exchange_rate' => $data['currencyExchangeRate'] ?? $activePrice->currency_exchange_rate,
                ]);
            } else {
                ProductPrice::create([
                    'pricable_id' => $clinicAsset->id,
                    'pricable_type' => ClinicAsset::class,
                    'price' => $data['price'],
                    'effective_from' => now(),
                    'is_active' => true,
                    'discount_percentage' => $data['discountPercentage'] ?? null,
                    'currency_exchange_rate' => $data['currencyExchangeRate'] ?? null,
                ]);
            }
        }

        return new ClinicAssetResource($clinicAsset->load(['Employee', 'Branch', 'activePrice']));
    }

    public function delete($id)
    {
        $clinicAsset = ClinicAsset::findOrFail($id);
        $clinicAsset->delete();

        return response()->json(['message' => 'Clinic asset deleted successfully']);
    }
}
