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
                'material_name' => 'mask',
                'amount' => 400,
                'total_amount' => 2000,
                'expense_date' => '2024/04/04',
                'quantity' => 5
            ],
            [
                'material_name' => 'clinic gloves',
                'amount' => 500,
                'total_amount' => 2500,
                'expense_date' => '2024/04/04',
                'quantity' => 5
            ],
            [
                'material_name' => 'dental syringe',
                'amount' => 150,
                'total_amount' => 1500,
                'expense_date' => '2024/04/04',
                'quantity' => 5
            ],
            [
                'material_name' => 'anesthetic',
                'amount' => 250,
                'total_amount' => 5000,
                'expense_date' => '2024/04/04',
                'quantity' => 5
            ],
            [
                'material_name' => 'drill bit',
                'amount' => 300,
                'total_amount' => 2400,
                'expense_date' => '2024/04/04',
                'quantity' => 5
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
