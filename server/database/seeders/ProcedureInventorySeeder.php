<?php

namespace Database\Seeders;

use App\Models\Procedure;
use App\Models\InventoryItem; // Assuming this model exists
use App\Models\ProcedureInventory;
use Illuminate\Database\Seeder;

class ProcedureInventorySeeder extends Seeder
{
    public function run(): void
    {
        $filling = Procedure::where('slug', 'composite-filling-1')->first();

        // Example: Link a Filling to specific items
        // We assume IDs 1=Masks, 2=Gloves, 3=Composite Syringe
        if ($filling) {
            ProcedureInventory::create([
                'procedure_id' => $filling->id,
                'inventory_stock_id' => 1, // Masks
                'unit_count' => 1, // Deducts 1 piece (2% of a 50-pack)
            ]);

            ProcedureInventory::create([
                'procedure_id' => $filling->id,
                'inventory_stock_id' => 3, // Composite
                'unit_count' => 2, // Deducts 2 "clicks" or "units"
            ]);
        }
    }
}
