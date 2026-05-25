<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
public function index()
{
    $branchId = $this->effectiveBranchId(request());

    $suppliers = Supplier::query()
        ->where('branch_id', $branchId)
        ->with([
            'items', // global catalog
            'orders' => function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            }
        ])
        ->withCount([
            'orders as orders_count' => function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            },
            'items' // total global items
        ])
        ->get();

    return SupplierResource::collection($suppliers);
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'contactPersonName' => 'required|string|max:255',
            'organizationName' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'itemIds' => 'nullable|array',
            'itemIds.*' => 'integer|exists:items,id',
            'status' => 'required|string|in:active,inactive',
            'businessId' => 'nullable|string|max:255',
            'address' => 'string',
            'notes' => 'string'
            // 'supplierId' => 'integer',
        ]);

        $supplier = Supplier::create([
            'contact_person_name' => $data['contactPersonName'],
            'organization_name' => $data['organizationName'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'status' => $data['status'],
            'business_id' => $data['businessId'] ?? null,
            'address' => $data['address'],
            'notes' => $data['notes'],
            'branch_id' => $this->effectiveBranchId($request),
        ]);

        // Sync items if provided
        if (isset($data['itemIds'])) {
            $supplier->items()->sync($data['itemIds']);
        }

        return new SupplierResource($supplier->load(['items']));
    }

    public function show($id)
    {
        $branchId = $this->effectiveBranchId(request());
        $supplier = Supplier::withCount('orders')
            ->with('items')
            ->where('branch_id', $branchId)
            ->findOrFail($id);
        return new SupplierResource($supplier);
    }

    public function update(Request $request, $id)
    {
        $branchId = $this->effectiveBranchId($request);
        $supplier = Supplier::where('branch_id', $branchId)->findOrFail($id);

        $data = $request->validate([
            'contactPersonName' => 'required|string|max:255',
            'organizationName' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'itemIds' => 'nullable|array',
            'itemIds.*' => 'integer|exists:items,id',
            'status' => 'required|string|in:active,inactive',
            'businessId' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string'
            // 'supplierId' => 'integer',
        ]);

        $supplier->update([
            'contact_person_name' => $data['contactPersonName'] ?? $supplier->contact_person_name,
            'organization_name' => $data['organizationName'] ?? $supplier->organization_name,
            'phone' => $data['phone'] ?? $supplier->phone,
            'email' => $data['email'] ?? $supplier->email,
            'status' => $data['status'] ?? $supplier->status,
            'business_id' => $data['businessId'] ?? $supplier->business_id,
            'branch_id' => $branchId,
            // 'supplier_id' => $data['supplierId'] ?? $supplier->supplier_id, // Optional parent supplier
            'notes' => $data['notes'],
            'address' => $data['address']
        ]);

        // Sync items if provided
        if (isset($data['itemIds'])) {
            $supplier->items()->sync($data['itemIds']);
        }

        return new SupplierResource($supplier->load(['items']));
    }

    public function delete($id)
    {
        $branchId = $this->effectiveBranchId(request());
        $supplier = Supplier::where('branch_id', $branchId)->findOrFail($id);
        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
