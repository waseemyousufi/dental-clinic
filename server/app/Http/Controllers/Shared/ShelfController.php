<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShelfResource;
use App\Models\Shelf;
use Illuminate\Http\Request;

class ShelfController extends Controller
{
    public function index()
    {
        $branchId = $this->effectiveBranchId(request());

        $query = Shelf::with(['inventoryStocks.stockable']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $shelves = $query->get();

        return ShelfResource::collection($shelves);
    }

    public function show($id)
    {
        $shelf = Shelf::with(['inventoryStock.stockable'])->findOrFail($id);
        return new ShelfResource($shelf);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shelfName' => 'required|string|max:255',
            'shelfType' => 'required|string|max:255',
            'accessPin' => 'nullable|string|max:50',
            'totalCapacityCm3' => 'required|numeric|min:0',
            'categoryRestriction' => 'nullable|string|max:255',
        ]);

        $shelf = Shelf::create([
            'shelf_name' => $data['shelfName'],
            'shelf_type' => $data['shelfType'],
            'access_pin' => $data['accessPin'] ?? null,
            'total_capacity_cm3' => $data['totalCapacityCm3'],
            'category_restriction' => $data['categoryRestriction'] ?? null,
            'branch_id' => $this->effectiveBranchId($request),
        ]);

        return new ShelfResource($shelf);
    }

    public function update(Request $request, $id)
    {
        $shelf = Shelf::findOrFail($id);

        $data = $request->validate([
            'shelfName' => 'sometimes|string|max:255',
            'shelfType' => 'sometimes|string|max:255',
            'accessPin' => 'nullable|string|max:50',
            'totalCapacityCm3' => 'sometimes|numeric|min:0',
            'categoryRestriction' => 'nullable|string|max:255',
        ]);

        $shelf->update([
            'shelf_name' => $data['shelfName'] ?? $shelf->shelf_name,
            'shelf_type' => $data['shelfType'] ?? $shelf->shelf_type,
            'access_pin' => $data['accessPin'] ?? $shelf->access_pin,
            'total_capacity_cm3' => $data['totalCapacityCm3'] ?? $shelf->total_capacity_cm3,
            'category_restriction' => $data['categoryRestriction'] ?? $shelf->category_restriction,
            'branch_id' => $this->effectiveBranchId($request),
        ]);

        return new ShelfResource($shelf);
    }

    public function delete($id)
    {
        $shelf = Shelf::findOrFail($id);
        $shelf->delete();

        return response()->json(['message' => 'Shelf deleted successfully']);
    }
}
