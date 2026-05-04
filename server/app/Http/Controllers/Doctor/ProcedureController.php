<?php
namespace App\Http\Controllers\Doctor;

use App\Models\Procedure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ProcedureController extends Controller
{
    /**
     * Get all active procedures with their inventory requirements.
     */
    public function index()
    {
        // Eager load the inventory requirements to show in the UI
        return Procedure::with('inventoryRequirements.stock')
            ->where('is_active', true)
            ->get();
    }

    /**
     * Create a new procedure and map its material consumption.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|unique:procedures,slug',
            'category' => 'required|string',
            'base_price' => 'required|numeric',
            'dentist_commission' => 'nullable|numeric',
            'assistant_commission' => 'nullable|numeric',
            // Array of inventory items: [{stock_id: 1, units: 2}, ...]
            'inventory' => 'nullable|array',
            'inventory.*.inventory_stock_id' => 'required|exists:inventory_stock,id',
            'inventory.*.unit_count' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Create the Procedure
            $procedure = Procedure::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'category' => $validated['category'],
                'base_price' => $validated['base_price'],
                'dentist_commission' => $validated['dentist_commission'] ?? 0,
                'assistant_commission' => $validated['assistant_commission'] ?? 0,
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

            return response()->json($procedure->load('inventoryRequirements'), 201);
        });
    }

    /**
     * Show a single procedure with its full Digital Twin mapping.
     */
    public function show(Procedure $procedure)
    {
        return $procedure->load('inventoryRequirements.stock');
    }

    /**
     * Update pricing or commissions.
     */
    public function update(Request $request, Procedure $procedure)
    {
        $validated = $request->validate([
            'base_price' => 'numeric',
            'dentist_commission' => 'numeric',
            'assistant_commission' => 'numeric',
            'is_active' => 'boolean',
        ]);

        $procedure->update($validated);

        return response()->json($procedure);
    }
}
