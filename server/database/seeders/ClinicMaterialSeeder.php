<?php

namespace Database\Seeders;

use App\Models\AccountTransaction;
use App\Models\Branch;
use App\Models\ClinicMaterial;
use App\Models\ProductPrice;
use App\Models\InventoryStock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;



class ClinicMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = [
            [
                'name' => 'Surgical Mask',
                'material_name' => 'mask',
                'description' => 'Disposable surgical masks',
                'category' => 'Consumable',
                'width' => 17.50,
                'height' => 9.50,
                'depth' => 0.50,
                'is_sterile' => false,
                'quantity' => 5,
                'amount' => 400,
                'total_amount' => 2000,
                'expense_date' => '2024/04/04',
                'description' => 'for daily use',
            ],
            [
                'name' => 'Examination Gloves',
                'material_name' => 'clinic gloves',
                'description' => 'Latex examination gloves',
                'category' => 'Consumable',
                'width' => 12.00,
                'height' => 6.00,
                'depth' => 4.00,
                'is_sterile' => false,
                'quantity' => 5,
                'amount' => 500,
                'total_amount' => 2500,
                'expense_date' => '2024/04/04',
                'description' => 'for daily use',
            ],
            [
                'name' => 'Dental Syringe',
                'material_name' => 'dental syringe',
                'description' => 'Standard dental syringe',
                'category' => 'Surgical',
                'width' => 15.00,
                'height' => 2.00,
                'depth' => 2.00,
                'is_sterile' => true,
                'quantity' => 10,
                'amount' => 150,
                'total_amount' => 1500,
                'expense_date' => '2024/04/04',
                'description' => 'surgical procedures',
            ],
            [
                'name' => 'Anesthetic Solution',
                'material_name' => 'anesthetic',
                'description' => 'Local anesthetic for dental procedures',
                'category' => 'Consumable',
                'width' => 5.00,
                'height' => 5.00,
                'depth' => 1.50,
                'is_sterile' => true,
                'quantity' => 20,
                'amount' => 250,
                'total_amount' => 5000,
                'expense_date' => '2024/04/04',
                'description' => 'pain management',
            ],
            [
                'name' => 'Dental Drill Bit',
                'material_name' => 'drill bit',
                'description' => 'Replacement drill bits',
                'category' => 'Surgical',
                'width' => 2.00,
                'height' => 2.00,
                'depth' => 0.50,
                'is_sterile' => true,
                'quantity' => 8,
                'amount' => 300,
                'total_amount' => 2400,
                'expense_date' => '2024/04/04',
                'description' => 'equipment replacement',
            ],
        ];

        $createdMaterials = [];
        foreach ($materials as $materialData) {
            $material = ClinicMaterial::create($materialData);
            $createdMaterials[] = $material;

            // Create active price
            ProductPrice::create([
                'pricable_id' => $material->id,
                'pricable_type' => ClinicMaterial::class,
                'price' => rand(10, 500) + 0.99,
                'effective_from' => now(),
                'is_active' => true,
            ]);
        }

        // Sync with account transactions and branches
        $transaction = AccountTransaction::find(3);
        $branch = Branch::find(1);

        foreach ($createdMaterials as $material) {
            if ($transaction) {
                $material->accountTransactions()->sync($transaction);
            }
            if ($branch) {
                $material->branches()->sync($branch);
            }
        }
    }
}
