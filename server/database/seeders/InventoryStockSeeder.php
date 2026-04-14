<?php

namespace Database\Seeders;

use App\Models\InventoryStock;
use App\Models\ClinicMaterial;
use App\Models\Shelf;
use Illuminate\Database\Seeder;

class InventoryStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = ClinicMaterial::all();
        $shelves = Shelf::all();

        if ($materials->isEmpty() || $shelves->isEmpty()) {
            $this->command->warn('Please run ClinicMaterialSeeder and ShelfSeeder first!');
            return;
        }

        $inventoryItems = [
            // Placed items on shelves
            [
                'material_index' => 0, // Surgical Mask
                'shelf_index' => 0,   // Glass Cabinet A1
                'quantity' => 50,
                'expiry_date' => now()->addMonths(12),
                'batch_number' => 'SM-2026-001',
                'status' => 'placed',
            ],
            [
                'material_index' => 1, // Examination Gloves
                'shelf_index' => 0,   // Glass Cabinet A1
                'quantity' => 100,
                'expiry_date' => now()->addMonths(18),
                'batch_number' => 'EG-2026-001',
                'status' => 'placed',
            ],
            [
                'material_index' => 2, // Dental Syringe
                'shelf_index' => 1,   // Metal Cabinet B1 (Surgical)
                'quantity' => 25,
                'expiry_date' => null,
                'batch_number' => 'DS-2026-001',
                'status' => 'placed',
            ],
            [
                'material_index' => 3, // Anesthetic Solution
                'shelf_index' => 2,   // Refrigerator R1
                'quantity' => 30,
                'expiry_date' => now()->addMonths(6),
                'batch_number' => 'AS-2026-001',
                'status' => 'placed',
            ],
            [
                'material_index' => 4, // Dental Drill Bit
                'shelf_index' => 1,   // Metal Cabinet B1 (Surgical)
                'quantity' => 15,
                'expiry_date' => null,
                'batch_number' => 'DB-2026-001',
                'status' => 'placed',
            ],
            // Pending distribution items (shelf_id = null)
            [
                'material_index' => 0, // Surgical Mask
                'shelf_index' => null, // Pending
                'quantity' => 20,
                'expiry_date' => now()->addMonths(12),
                'batch_number' => 'SM-2026-002',
                'status' => 'pending',
            ],
            [
                'material_index' => 1, // Examination Gloves
                'shelf_index' => null, // Pending
                'quantity' => 40,
                'expiry_date' => now()->addMonths(18),
                'batch_number' => 'EG-2026-002',
                'status' => 'pending',
            ],
        ];

        foreach ($inventoryItems as $item) {
            InventoryStock::create([
                'stockable_id' => $materials[$item['material_index']]->id,
                'stockable_type' => ClinicMaterial::class,
                'shelf_id' => $item['shelf_index'] !== null ? $shelves[$item['shelf_index']]->id : null,
                'quantity' => $item['quantity'],
                'expiry_date' => $item['expiry_date'],
                'batch_number' => $item['batch_number'],
                'status' => $item['status'],
            ]);
        }
    }
}
