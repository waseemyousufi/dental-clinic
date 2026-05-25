<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\ClinicAsset;
use App\Models\ClinicMaterial;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $branchId = $this->effectiveBranchId(request());

        // Start the query
        $query = Order::query();

        // Filter by branch if a specific branch ID is effective
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Eager load relationships and count items
        $orders = $query->with(['supplier', 'items.item'])
            ->withCount('items')
            ->get();

        return OrderResource::collection($orders);
    }

    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'supplierName' => 'required|string|max:255',
            'supplierId' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'status' => 'required|string|in:pending,received,cancelled,draft',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.itemId' => 'required|integer|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unitPrice' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $branchId) {

            $order = Order::create([
                'supplier_id' => $data['supplierId'],
                'date' => $data['date'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'branch_id' => $branchId,
                'supplier_name' => $data['supplierName'],
            ]);

            foreach ($data['items'] as $itemData) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $itemData['itemId'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unitPrice'],
                    'total_price' => $itemData['unitPrice'] * $itemData['quantity'],
                ]);
            }

            return new OrderResource($order->load(['supplier', 'items.item']));
        });
    }

    public function show($id)
    {
        $branchId = $this->effectiveBranchId(request());
        $order = Order::with(['supplier', 'items.item'])->withCount('items')
            ->where('branch_id', $branchId)
            ->findOrFail($id);
        return new OrderResource($order);
    }

    public function update(Request $request, $id)
    {
        $branchId = $this->effectiveBranchId($request);
        $order = Order::with('items')->where('branch_id', $branchId)->findOrFail($id);

        $data = $request->validate([
            'supplierId' => 'sometimes|exists:suppliers,id',
            'date' => 'sometimes|date',
            'status' => 'sometimes|string|in:pending,received,cancelled,draft',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array',
            'items.*.itemId' => 'required_with:items|integer|exists:items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unitPrice' => 'required_with:items|numeric|min:0',
        ]);

        $oldStatus = $order->status;

        // ✅ Update order
        $order->update([
            'supplier_id' => $data['supplierId'] ?? $order->supplier_id,
            'date' => $data['date'] ?? $order->date,
            'status' => $data['status'] ?? $order->status,
            'notes' => $data['notes'] ?? $order->notes,
            'branch_id' => $branchId,
        ]);

        // ✅ Update items if provided
        if (isset($data['items'])) {
            $order->items()->delete();

            foreach ($data['items'] as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $itemData['itemId'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unitPrice'],
                    'total_price' => $itemData['unitPrice'] * $itemData['quantity'],
                ]);
            }

            // reload items after replace
            $order->load('items');
        }

        if (
            isset($data['status']) &&
            $data['status'] === 'received' &&
            $oldStatus !== 'received'
        ) {
            foreach ($order->items as $orderItem) {

                $item = Item::where('branch_id', $branchId)->findOrFail($orderItem->item_id);

                // ✅ Decide type based on item
                if ($item->is_consumable) {

                    // 🔹 Create ClinicMaterial
                    $material = ClinicMaterial::create([
                        'name' => $item->name,
                        'material_name' => $item->name,
                        'quantity' => $orderItem->quantity,
                        'amount' => $orderItem->unit_price,
                        'total_amount' => $orderItem->total_price,
                        'expense_date' => now(),
                    ]);

                    // attach to branch (since you use pivot)
                    $material->branches()->syncWithoutDetaching([$branchId]);

                    // 🔹 Create InventoryStock
                    InventoryStock::create([
                        'stockable_id' => $material->id,
                        'stockable_type' => ClinicMaterial::class,
                        'shelf_id' => null,
                        'quantity' => $orderItem->quantity,
                        'status' => 'pending',
                        'branch_id' => $branchId,
                    ]);
                } else {

                    // 🔹 Create ClinicAsset
                    $asset = ClinicAsset::create([
                        'asset_name' => $item->name,
                        'amount' => $orderItem->quantity,
                        'price' => $orderItem->unit_price,
                        'total_amount' => $orderItem->total_price,
                        'date_of_purchase' => now(),
                        'status' => 'active',
                        'branch_id' => $branchId,
                        'category' => $this->mapItemToAssetCategory($item),
                        'purchasedByEmployee_id' => Auth::user()->employee->id, // ✅ FIX
                    ]);

                    // 🔹 Create InventoryStock
                    InventoryStock::create([
                        'stockable_id' => $asset->id,
                        'stockable_type' => ClinicAsset::class,
                        'shelf_id' => null,
                        'quantity' => $orderItem->quantity,
                        'status' => 'pending',
                        'branch_id' => $branchId,
                    ]);
                }
            }
        }
        return new OrderResource($order->load(['supplier', 'items.item']));
    }

    private function mapItemToAssetCategory($item)
    {
        return match ($item->category) {
            'devices' => 'device',
            'furniture' => 'furniture',
            default => 'device', // fallback (important)
        };
    }

    // ✅ Helper: Determine correct polymorphic type for the item
    private function getStockableType(int $itemId): string
    {
        // Option 1: If all items are ClinicMaterial
        return \App\Models\ClinicMaterial::class;

        // Option 2: If you have multiple stockable types, query to determine:
        // $item = \App\Models\Item::find($itemId);
        // return $item?->stockable_type ?? \App\Models\ClinicMaterial::class;
    }

    public function delete($id)
    {
        $branchId = $this->effectiveBranchId(request());
        $order = Order::where('branch_id', $branchId)->findOrFail($id);
        $order->items()->delete();
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
