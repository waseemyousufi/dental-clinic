<?php
namespace App\Http\Controllers\Doctor;

use App\Models\Procedure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProcedureResource;

class ProcedureController extends Controller
{
    /**
     * Get all active procedures with their inventory requirements.
     */
    public function index()
    {
        return ProcedureResource::collection(
            Procedure::with('inventoryRequirements.stock')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Create a new procedure and map its material consumption.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|unique:procedures,slug',
            'category' => 'required|string',
            'base_price' => 'required|numeric',
            'min_price' => 'required|numeric|lte:base_price',
            'dentist_commission' => 'nullable|numeric',
            'assistant_commission' => 'nullable|numeric',
            'appointments_needed' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
            // Array of inventory items: [{stock_id: 1, units: 2}, ...]
            'inventory' => 'nullable|array',
            'inventory.*.inventory_stock_id' => 'required|exists:inventory_stock,id',
            'inventory.*.unit_count' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Create the Procedure
            $procedure = Procedure::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'] ?? Str::slug($validated['name']),
                'category' => $validated['category'],
                'base_price' => $validated['base_price'],
                'min_price' => $validated['min_price'],
                'appointments_needed' => $validated['appointments_needed'],
                'dentist_commission' => $validated['dentist_commission'] ?? 0,
                'assistant_commission' => $validated['assistant_commission'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // 2. Attach Inventory Requirements (The Bridge Table)
            if (!empty($validated['inventory'])) {
                foreach ($validated['inventory'] as $item) {
                    $procedure->inventoryRequirements()->create([
                        'inventory_stock_id' => $item['inventory_stock_id'],
                        'unit_count' => $item['unit_count'],
                        'is_optional' => false,
                    ]);
                }
            }

            return (new ProcedureResource($procedure->load('inventoryRequirements.stock')))
                ->response()
                ->setStatusCode(201);
        });
    }

    /**
     * Show a single procedure with its full Digital Twin mapping.
     */
    public function show(Procedure $procedure)
    {
        return new ProcedureResource($procedure->load('inventoryRequirements.stock'));
    }

    /**
     * Update pricing or commissions.
     */
    public function update(Request $request, Procedure $procedure)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'slug' => 'nullable|string|unique:procedures,slug,' . $procedure->id,
            'category' => 'sometimes|string',
            'base_price' => 'numeric',
            'min_price' => 'nullable|numeric',
            'dentist_commission' => 'numeric',
            'assistant_commission' => 'numeric',
            'is_active' => 'boolean',
            'appointments_needed' => 'nullable|integer|min:0',
        ]);

        if (!empty($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $procedure->update($validated);

        return new ProcedureResource($procedure->fresh('inventoryRequirements.stock'));
    }

    public function destroy(Procedure $procedure)
    {
        $procedure->delete();

        return response()->json(['message' => 'Procedure deleted successfully']);
    }
}
