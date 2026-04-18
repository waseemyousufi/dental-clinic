<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClinicMaterialResource;
use App\Models\Branch;
use App\Models\ClinicMaterial;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicMaterialController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $branch = Branch::findOrFail($branchId);
        $clinicMaterials = $branch->ClinicMaterial()
            ->with(['activePrice', 'inventoryStock.shelf'])
            ->get();
        return ClinicMaterialResource::collection($clinicMaterials);
    }

    public function show($id)
    {
        $clinicMaterial = ClinicMaterial::with(['activePrice', 'prices', 'inventoryStock.shelf', 'branches', 'accountTransactions'])
            ->findOrFail($id);
        return new ClinicMaterialResource($clinicMaterial);
    }

    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'materialName' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'isSterile' => 'boolean',
            'quantity' => 'required|integer|min:0',
            'amount' => 'required|numeric|min:0',
            'totalAmount' => 'required|numeric|min:0',
            'expenseDate' => 'required|date',
            'price' => 'nullable|numeric|min:0',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            'currencyExchangeRate' => 'nullable|numeric|min:0',
        ]);

        $branch = Branch::findOrFail($branchId);

        $clinicMaterial = ClinicMaterial::create([
            'name' => $data['name'],
            'material_name' => $data['materialName'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'depth' => $data['depth'] ?? null,
            'is_sterile' => $data['isSterile'] ?? false,
            'quantity' => $data['quantity'],
            'amount' => $data['amount'],
            'total_amount' => $data['totalAmount'],
            'expense_date' => $data['expenseDate'],
        ]);

        // Sync with branch
        $clinicMaterial->branches()->sync([$branch->id]);

        // Create price if provided
        if (isset($data['price'])) {
            ProductPrice::create([
                'pricable_id' => $clinicMaterial->id,
                'pricable_type' => ClinicMaterial::class,
                'price' => $data['price'],
                'effective_from' => now(),
                'is_active' => true,
                'discount_percentage' => $data['discountPercentage'] ?? null,
                'currency_exchange_rate' => $data['currencyExchangeRate'] ?? null,
            ]);
        }

        return new ClinicMaterialResource($clinicMaterial->load(['activePrice', 'branches']));
    }

    public function update(Request $request, $id)
    {
        $clinicMaterial = ClinicMaterial::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'materialName' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'isSterile' => 'boolean',
            'quantity' => 'sometimes|integer|min:0',
            'amount' => 'sometimes|numeric|min:0',
            'totalAmount' => 'sometimes|numeric|min:0',
            'expenseDate' => 'sometimes|date',
            'price' => 'nullable|numeric|min:0',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            'currencyExchangeRate' => 'nullable|numeric|min:0',
        ]);

        $clinicMaterial->update([
            'name' => $data['name'] ?? $clinicMaterial->name,
            'material_name' => $data['materialName'] ?? $clinicMaterial->material_name,
            'description' => $data['description'] ?? $clinicMaterial->description,
            'category' => $data['category'] ?? $clinicMaterial->category,
            'width' => $data['width'] ?? $clinicMaterial->width,
            'height' => $data['height'] ?? $clinicMaterial->height,
            'depth' => $data['depth'] ?? $clinicMaterial->depth,
            'is_sterile' => $data['isSterile'] ?? $clinicMaterial->is_sterile,
            'quantity' => $data['quantity'] ?? $clinicMaterial->quantity,
            'amount' => $data['amount'] ?? $clinicMaterial->amount,
            'total_amount' => $data['totalAmount'] ?? $clinicMaterial->total_amount,
            'expense_date' => $data['expenseDate'] ?? $clinicMaterial->expense_date,
        ]);

        // Update price if provided
        if (isset($data['price'])) {
            $activePrice = $clinicMaterial->activePrice;
            if ($activePrice) {
                $activePrice->update([
                    'price' => $data['price'],
                    'discount_percentage' => $data['discountPercentage'] ?? $activePrice->discount_percentage,
                    'currency_exchange_rate' => $data['currencyExchangeRate'] ?? $activePrice->currency_exchange_rate,
                ]);
            } else {
                ProductPrice::create([
                    'pricable_id' => $clinicMaterial->id,
                    'pricable_type' => ClinicMaterial::class,
                    'price' => $data['price'],
                    'effective_from' => now(),
                    'is_active' => true,
                    'discount_percentage' => $data['discountPercentage'] ?? null,
                    'currency_exchange_rate' => $data['currencyExchangeRate'] ?? null,
                ]);
            }
        }

        return new ClinicMaterialResource($clinicMaterial->load(['activePrice', 'branches']));
    }

    public function delete($id)
    {
        $clinicMaterial = ClinicMaterial::findOrFail($id);
        $clinicMaterial->delete();

        return response()->json(['message' => 'Clinic material deleted successfully']);
    }
}
