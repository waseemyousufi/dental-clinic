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
        $suppliers = Supplier::withCount('orders')->get();
        return SupplierResource::collection($suppliers);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contactPersonName' => 'required|string|max:255',
            'organizationName' => 'required|string|max:255|unique:suppliers,organization_name',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'itemIds' => 'nullable|array',
            'itemIds.*' => 'integer|exists:items,id',
            'status' => 'required|string|in:active,inactive',
            'businessId' => 'nullable|string|max:255',
        ]);

        $supplier = Supplier::create([
            'contact_person_name' => $data['contactPersonName'],
            'organization_name' => $data['organizationName'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'status' => $data['status'],
            'business_id' => $data['businessId'] ?? null,
        ]);

        // Sync items if provided
        if (isset($data['itemIds'])) {
            $supplier->items()->sync($data['itemIds']);
        }

        return new SupplierResource($supplier->loadCount('items'));
    }

    public function show($id)
    {
        $supplier = Supplier::withCount('orders')->with('items')->findOrFail($id);
        return new SupplierResource($supplier);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $data = $request->validate([
            'contactPersonName' => 'sometimes|string|max:255',
            'organizationName' => 'sometimes|string|max:255|unique:suppliers,organization_name,' . $id,
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'itemIds' => 'nullable|array',
            'itemIds.*' => 'integer|exists:items,id',
            'status' => 'sometimes|string|in:active,inactive',
            'businessId' => 'nullable|string|max:255',
        ]);

        $supplier->update([
            'contact_person_name' => $data['contactPersonName'] ?? $supplier->contact_person_name,
            'organization_name' => $data['organizationName'] ?? $supplier->organization_name,
            'phone' => $data['phone'] ?? $supplier->phone,
            'email' => $data['email'] ?? $supplier->email,
            'status' => $data['status'] ?? $supplier->status,
            'business_id' => $data['businessId'] ?? $supplier->business_id,
        ]);

        // Sync items if provided
        if (isset($data['itemIds'])) {
            $supplier->items()->sync($data['itemIds']);
        }

        return new SupplierResource($supplier->load(['items']));
    }

    public function delete($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
