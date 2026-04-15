<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['supplier', 'items.item'])->withCount('items')->get();
        return OrderResource::collection($orders);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplierName' => 'required|string|max:255',
            'date' => 'required|date',
            'status' => 'required|string|in:pending,received,cancelled,draft',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.itemId' => 'required|integer|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unitPrice' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'supplier_name' => $data['supplierName'],
                'date' => $data['date'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $itemData) {
                $unitPrice = $itemData['unitPrice'];
                $quantity = $itemData['quantity'];
                $totalPrice = $unitPrice * $quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $itemData['itemId'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
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
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'supplierName' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'status' => 'sometimes|string|in:pending,received,cancelled,draft',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array',
            'items.*.itemId' => 'required_with:items|integer|exists:items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unitPrice' => 'required_with:items|numeric|min:0',
        ]);

        $order->update([
            'supplier_name' => $data['supplierName'] ?? $order->supplier_name,
            'date' => $data['date'] ?? $order->date,
            'status' => $data['status'] ?? $order->status,
            'notes' => $data['notes'] ?? $order->notes,
        ]);

        if (isset($data['items'])) {
            $order->items()->delete();

            foreach ($data['items'] as $itemData) {
                $unitPrice = $itemData['unitPrice'];
                $quantity = $itemData['quantity'];
                $totalPrice = $unitPrice * $quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $itemData['itemId'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
            }
        }

        return new OrderResource($order->load(['supplier', 'items.item']));
    }

    public function delete($id)
    {
        $order = Order::findOrFail($id);
        $order->items()->delete();
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
