<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
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
        $order = Order::with(['supplier', 'items.item'])->withCount('items')->findOrFail($id);
        return new OrderResource($order);
    }

public function update(Request $request, $id)
{
    $order = Order::with('items')->findOrFail($id);
    $branchId = $this->effectiveBranchId($request);

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

    // ✅ Create inventory stock when order is received
    if (
        isset($data['status']) &&
        $data['status'] === 'received' &&
        $oldStatus !== 'received'
    ) {
        foreach ($order->items as $orderItem) {
            // ✅ FIXED: Use the actual item_id as stockable_id
            InventoryStock::create([
                'stockable_id' => $orderItem->item_id,  // ✅ Was: ''
                'stockable_type' => $this->getStockableType($orderItem->item_id), // ✅ Dynamic or hardcoded
                'shelf_id' => null,
                'quantity' => $orderItem->quantity,
                'status' => 'pending', // or 'available' if stock is immediately usable
                'branch_id' => $branchId,
                'batch_number' => null,
                'expiry_date' => null,
            ]);
        }
    }

    return new OrderResource($order->load(['supplier', 'items.item']));
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
        $order = Order::findOrFail($id);
        $order->items()->delete();
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
