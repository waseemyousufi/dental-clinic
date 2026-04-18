<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryStockResource;
use App\Models\InventoryStock;
use App\Models\ClinicMaterial;
use App\Models\ClinicAsset;
use App\Models\Shelf;
use Illuminate\Http\Request;

class InventoryStockController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $query = InventoryStock::with(['stockable', 'shelf']);
        $query->where('branch_id', $branchId);

        $inventoryStock = $query->get();
        return InventoryStockResource::collection($inventoryStock);
    }

    public function show(Request $request, $id)
    {
        $branchId = $this->effectiveBranchId($request);
        $query = InventoryStock::with(['stockable', 'shelf']);
        $query->where('branch_id', $branchId);

        $inventoryStock = $query->findOrFail($id);
        return new InventoryStockResource($inventoryStock);
    }

    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $data = $request->validate([
            'stockableType' => 'required|string|in:App\Models\ClinicMaterial,App\Models\ClinicAsset',
            'stockableId' => 'required|integer',
            'shelfId' => 'nullable|integer|exists:shelves,id',
            'quantity' => 'required|integer|min:1',
            'expiryDate' => 'nullable|date',
            'batchNumber' => 'nullable|string|max:50',
            'status' => 'required|string|in:placed,pending',
            'branchId' => 'sometimes|integer|exists:branches,id',
        ]);

        // Validate stockable exists
        if ($data['stockableType'] === 'App\Models\ClinicMaterial') {
            ClinicMaterial::findOrFail($data['stockableId']);
        } else {
            ClinicAsset::findOrFail($data['stockableId']);
        }

        // Validate shelf capacity if placing on shelf
        if (isset($data['shelfId'])) {
            $shelf = Shelf::findOrFail($data['shelfId']);
            $stockable = $data['stockableType']::find($data['stockableId']);
            
            if ($stockable && $stockable->width && $stockable->height && $stockable->depth) {
                $itemVolume = $stockable->width * $stockable->height * $stockable->depth * $data['quantity'];
                if ($shelf->available_capacity < $itemVolume) {
                    return response()->json([
                        'message' => 'Insufficient shelf capacity',
                        'available' => $shelf->available_capacity,
                        'required' => $itemVolume,
                    ], 422);
                }
            }
        }

        $inventoryStock = InventoryStock::create([
            'stockable_id' => $data['stockableId'],
            'stockable_type' => $data['stockableType'],
            'shelf_id' => $data['shelfId'] ?? null,
            'quantity' => $data['quantity'],
            'expiry_date' => $data['expiryDate'] ?? null,
            'batch_number' => $data['batchNumber'] ?? null,
            'status' => $data['status'],
            'branch_id' => $branchId,
        ]);

        return new InventoryStockResource($inventoryStock->load(['stockable', 'shelf']));
    }

    public function update(Request $request, $id)
    {
        $branchId = $this->effectiveBranchId($request);
        $inventoryStock = InventoryStock::findOrFail($id);

        $data = $request->validate([
            'stockableType' => 'sometimes|string|in:App\Models\ClinicMaterial,App\Models\ClinicAsset',
            'stockableId' => 'sometimes|integer',
            'shelfId' => 'nullable|integer|exists:shelves,id',
            'quantity' => 'sometimes|integer|min:1',
            'expiryDate' => 'nullable|date',
            'batchNumber' => 'nullable|string|max:50',
            'status' => 'sometimes|string|in:placed,pending',
            'branchId' => 'sometimes|integer|exists:branches,id',
        ]);

        // Validate stockable if changing
        if (isset($data['stockableId']) && isset($data['stockableType'])) {
            if ($data['stockableType'] === 'App\Models\ClinicMaterial') {
                ClinicMaterial::findOrFail($data['stockableId']);
            } else {
                ClinicAsset::findOrFail($data['stockableId']);
            }
        }

        // Validate shelf capacity if changing shelf or quantity
        if (isset($data['shelfId']) || isset($data['quantity'])) {
            $shelfId = $data['shelfId'] ?? $inventoryStock->shelf_id;
            if ($shelfId) {
                $shelf = Shelf::findOrFail($shelfId);
                $stockableType = $data['stockableType'] ?? $inventoryStock->stockable_type;
                $stockableId = $data['stockableId'] ?? $inventoryStock->stockable_id;
                $quantity = $data['quantity'] ?? $inventoryStock->quantity;
                
                $stockable = $stockableType::find($stockableId);
                if ($stockable && $stockable->width && $stockable->height && $stockable->depth) {
                    $itemVolume = $stockable->width * $stockable->height * $stockable->depth * $quantity;
                    if ($shelf->available_capacity < $itemVolume) {
                        return response()->json([
                            'message' => 'Insufficient shelf capacity',
                            'available' => $shelf->available_capacity,
                            'required' => $itemVolume,
                        ], 422);
                    }
                }
            }
        }

        $inventoryStock->update([
            'stockable_id' => $data['stockableId'] ?? $inventoryStock->stockable_id,
            'stockable_type' => $data['stockableType'] ?? $inventoryStock->stockable_type,
            'shelf_id' => $data['shelfId'] ?? $inventoryStock->shelf_id,
            'quantity' => $data['quantity'] ?? $inventoryStock->quantity,
            'expiry_date' => $data['expiryDate'] ?? $inventoryStock->expiry_date,
            'batch_number' => $data['batchNumber'] ?? $inventoryStock->batch_number,
            'status' => $data['status'] ?? $inventoryStock->status,
            'branch_id' => $branchId,
        ]);

        return new InventoryStockResource($inventoryStock->load(['stockable', 'shelf']));
    }

    public function delete($id)
    {
        $inventoryStock = InventoryStock::findOrFail($id);
        $inventoryStock->delete();

        return response()->json(['message' => 'Inventory stock deleted successfully']);
    }

    /**
     * Get pending inventory items
     */
    public function pending(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $query = InventoryStock::with(['stockable', 'shelf'])
            ->pending();
        $query->where('branch_id', $branchId);

        $inventoryStock = $query->get();
        return InventoryStockResource::collection($inventoryStock);
    }

    /**
     * Get placed inventory items
     */
    public function placed(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $query = InventoryStock::with(['stockable', 'shelf'])
            ->placed();
        $query->where('branch_id', $branchId);

        $inventoryStock = $query->get();
        return InventoryStockResource::collection($inventoryStock);
    }
}
