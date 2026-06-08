<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClinicAssetResource;
use App\Models\Branch;
use App\Models\ClinicAsset;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClinicAssetController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $branch = Branch::findOrFail($branchId);

        $clinicAssets = $branch->ClinicAsset()
            ->with(['Employee', 'Branch', 'activePrice', 'inventoryStock.shelf'])
            ->get();

        return ClinicAssetResource::collection($clinicAssets);
    }

    public function show($id)
    {
        $branchId = $this->effectiveBranchId(request());
        $clinicAsset = ClinicAsset::with(['Employee', 'Branch', 'activePrice', 'prices', 'inventoryStock.shelf'])
            ->where('branch_id', $branchId)
            ->findOrFail($id);

        return new ClinicAssetResource($clinicAsset);
    }

    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $data = $this->validateAssetPayload($request, false);

        $clinicAsset = DB::transaction(function () use ($data, $branchId) {
            $clinicAsset = ClinicAsset::create([
                // 'name' => $data['name'],
                'name' => $data['assetName'], // Using assetName as the main name field for now
                'asset_name' => $data['assetName'],
                // 'description' => $data['description'] ?? null,
                'category' => $data['category'],
                // 'width' => $data['width'] ?? null,
                // 'height' => $data['height'] ?? null,
                // 'depth' => $data['depth'] ?? null,
                // 'is_sterile' => $data['isSterile'] ?? false,
                'amount' => $data['amount'],
                'price' => $data['price'],
                'total_amount' => $data['totalAmount'],
                'date_of_purchase' => $data['dateOfPurchase'],
                'status' => $data['status'],
                'purchasedByEmployee_id' => Auth::user()?->employee?->id,
                'branch_id' => $branchId,
            ]);

            $this->syncPrice($clinicAsset, $data);

            return $clinicAsset;
        });

        return new ClinicAssetResource($clinicAsset->load(['Employee', 'Branch', 'activePrice']));
    }

    public function update(Request $request, $id)
    {
        $branchId = $this->effectiveBranchId($request);
        $clinicAsset = ClinicAsset::with('activePrice')
            ->where('branch_id', $branchId)
            ->findOrFail($id);
        $data = $this->validateAssetPayload($request, true);

        DB::transaction(function () use ($clinicAsset, $data, $branchId) {
            $clinicAsset->update([
                'name' => $data['assetName'] ?? $clinicAsset->name,
                'asset_name' => $data['assetName'] ?? $clinicAsset->asset_name,
                // 'description' => array_key_exists('description', $data) ? $data['description'] : $clinicAsset->description,
                'category' => $data['category'] ?? $clinicAsset->category,
                // 'width' => array_key_exists('width', $data) ? $data['width'] : $clinicAsset->width,
                // 'height' => array_key_exists('height', $data) ? $data['height'] : $clinicAsset->height,
                // 'depth' => array_key_exists('depth', $data) ? $data['depth'] : $clinicAsset->depth,
                // 'is_sterile' => array_key_exists('isSterile', $data) ? (bool) $data['isSterile'] : $clinicAsset->is_sterile,
                'amount' => $data['amount'] ?? $clinicAsset->amount,
                'price' => $data['price'] ?? $clinicAsset->price,
                'total_amount' => $data['totalAmount'] ?? $clinicAsset->total_amount,
                'date_of_purchase' => $data['dateOfPurchase'] ?? $clinicAsset->date_of_purchase,
                'status' => $data['status'] ?? $clinicAsset->status,
                'purchasedByEmployee_id' => array_key_exists('purchasedByEmployeeId', $data)
                    ? $data['purchasedByEmployeeId']
                    : $clinicAsset->purchasedByEmployee_id,
                'branch_id' => $branchId,
            ]);

            if (array_key_exists('price', $data)) {
                $this->syncPrice($clinicAsset, $data);
            }
        });

        return new ClinicAssetResource($clinicAsset->refresh()->load(['Employee', 'Branch', 'activePrice']));
    }

    public function delete($id)
    {
        $branchId = $this->effectiveBranchId(request());
        $clinicAsset = ClinicAsset::where('branch_id', $branchId)->findOrFail($id);
        $clinicAsset->delete();

        return response()->json(['message' => 'Clinic asset deleted successfully']);
    }

    private function validateAssetPayload(Request $request, bool $isUpdate = false): array
    {
        $data = $this->normalizeAssetPayload($request->all());

        $rules = [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'assetName' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            // 'description' => ['sometimes', 'nullable', 'string'],
            'category' => [$isUpdate ? 'sometimes' : 'required', 'string', Rule::in(['device', 'furniture'])],
            'width' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'height' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'depth' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'isSterile' => ['sometimes', 'boolean'],
            'amount' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:0'],
            'price' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'totalAmount' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'dateOfPurchase' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'status' => [$isUpdate ? 'sometimes' : 'required', 'string', Rule::in(['active', 'inactive', 'maintenance'])],
            'purchasedByEmployeeId' => ['sometimes', 'nullable', 'integer', 'exists:employees,id'],
            'discountPercentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'currencyExchangeRate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];

        return validator($data, $rules)->validate();
    }

    private function normalizeAssetPayload(array $input): array
    {
        $map = [
            'name' => 'name',
            'asset_name' => 'assetName',
            'assetName' => 'assetName',
            // 'description' => 'description',
            'category' => 'category',
            'width' => 'width',
            'height' => 'height',
            'depth' => 'depth',
            'is_sterile' => 'isSterile',
            'isSterile' => 'isSterile',
            'amount' => 'amount',
            'price' => 'price',
            'total_amount' => 'totalAmount',
            'totalAmount' => 'totalAmount',
            'date_of_purchase' => 'dateOfPurchase',
            'dateOfPurchase' => 'dateOfPurchase',
            'status' => 'status',
            'purchased_by_employee_id' => 'purchasedByEmployeeId',
            'purchasedByEmployeeId' => 'purchasedByEmployeeId',
            'discount_percentage' => 'discountPercentage',
            'discountPercentage' => 'discountPercentage',
            'currency_exchange_rate' => 'currencyExchangeRate',
            'currencyExchangeRate' => 'currencyExchangeRate',
        ];

        $normalized = [];

        foreach ($map as $source => $target) {
            if (!array_key_exists($source, $input)) {
                continue;
            }

            $value = $input[$source];

            if ($value === '') {
                $value = null;
            }

            if ($target === 'isSterile' && $value !== null) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $value = $value ?? false;
            }

            $normalized[$target] = $value;
        }

        return $normalized;
    }

    private function syncPrice(ClinicAsset $clinicAsset, array $data): void
    {
        $activePrice = $clinicAsset->activePrice;

        if ($activePrice) {
            $activePrice->update([
                'price' => $data['price'],
                'discount_percentage' => $data['discountPercentage'] ?? $activePrice->discount_percentage,
                'currency_exchange_rate' => $data['currencyExchangeRate'] ?? $activePrice->currency_exchange_rate,
            ]);

            return;
        }

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
