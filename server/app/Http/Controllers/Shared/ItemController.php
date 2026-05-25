<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\ProductPrice;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $query = Item::with(['activePrice', 'suppliers', 'inventoryStocks'])
            ->where('branch_id', $branchId);

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by consumable
        if ($request->has('consumable')) {
            $query->where('is_consumable', $request->boolean('consumable'));
        }

        // Filter by stock tracking
        if ($request->has('track_stock')) {
            $query->where('track_stock', $request->boolean('track_stock'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $items = $query->get();
        return ItemResource::collection($items);
    }

    public function show($id)
    {
        $branchId = $this->effectiveBranchId(request());
        $item = Item::with(['activePrice', 'prices', 'inventoryStocks.shelf', 'suppliers'])
            ->where('branch_id', $branchId)
            ->findOrFail($id);
        return new ItemResource($item);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:prosthetics,devices,furniture,instruments,medications,consumables',
            'materials' => 'nullable|array',
            'materials.*' => 'string',
            'description' => 'nullable|string',
            'trackStock' => 'boolean',
            'requiresBatch' => 'boolean',
            'requiresExpiry' => 'boolean',
            'isConsumable' => 'boolean',
            'price' => 'nullable|numeric|min:0',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            'currencyExchangeRate' => 'nullable|numeric|min:0',
        ]);

        $item = Item::create([
            'branch_id' => $this->effectiveBranchId($request),
            'name' => $data['name'],
            'category' => $data['category'],
            'materials' => $data['materials'] ?? [],
            'description' => $data['description'] ?? null,
            'track_stock' => $data['trackStock'] ?? false,
            'requires_batch' => $data['requiresBatch'] ?? false,
            'requires_expiry' => $data['requiresExpiry'] ?? false,
            'is_consumable' => $data['isConsumable'] ?? false,
        ]);

        // Create price if provided
        if (isset($data['price'])) {
            ProductPrice::create([
                'pricable_id' => $item->id,
                'pricable_type' => Item::class,
                'price' => $data['price'],
                'effective_from' => now(),
                'is_active' => true,
                'discount_percentage' => $data['discountPercentage'] ?? null,
                'currency_exchange_rate' => $data['currencyExchangeRate'] ?? null,
            ]);
        }

        return new ItemResource($item->load(['activePrice']));
    }

    public function update(Request $request, $id)
    {
        $branchId = $this->effectiveBranchId($request);
        $item = Item::where('branch_id', $branchId)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|in:prosthetics,devices,furniture,instruments,medications,consumables',
            'materials' => 'nullable|array',
            'materials.*' => 'string',
            'description' => 'nullable|string',
            'trackStock' => 'boolean',
            'requiresBatch' => 'boolean',
            'requiresExpiry' => 'boolean',
            'isConsumable' => 'boolean',
            'price' => 'nullable|numeric|min:0',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            'currencyExchangeRate' => 'nullable|numeric|min:0',
        ]);

        $item->update([
            'name' => $data['name'] ?? $item->name,
            'category' => $data['category'] ?? $item->category,
            'materials' => $data['materials'] ?? $item->materials,
            'description' => $data['description'] ?? $item->description,
            'track_stock' => $data['trackStock'] ?? $item->track_stock,
            'requires_batch' => $data['requiresBatch'] ?? $item->requires_batch,
            'requires_expiry' => $data['requiresExpiry'] ?? $item->requires_expiry,
            'is_consumable' => $data['isConsumable'] ?? $item->is_consumable,
        ]);

        // Update price if provided
        if (isset($data['price'])) {
            $activePrice = $item->activePrice;
            if ($activePrice) {
                $activePrice->update([
                    'price' => $data['price'],
                    'discount_percentage' => $data['discountPercentage'] ?? $activePrice->discount_percentage,
                    'currency_exchange_rate' => $data['currencyExchangeRate'] ?? $activePrice->currency_exchange_rate,
                ]);
            } else {
                ProductPrice::create([
                    'pricable_id' => $item->id,
                    'pricable_type' => Item::class,
                    'price' => $data['price'],
                    'effective_from' => now(),
                    'is_active' => true,
                    'discount_percentage' => $data['discountPercentage'] ?? null,
                    'currency_exchange_rate' => $data['currencyExchangeRate'] ?? null,
                ]);
            }
        }

        return new ItemResource($item->load(['activePrice']));
    }

    public function delete($id)
    {
        $branchId = $this->effectiveBranchId(request());
        $item = Item::where('branch_id', $branchId)->findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Item deleted successfully']);
    }

    /**
     * Get all available categories
     */
    public function categories()
    {
        return response()->json([
            'categories' => Item::distinct()->pluck('category')->values(),
        ]);
    }
}
