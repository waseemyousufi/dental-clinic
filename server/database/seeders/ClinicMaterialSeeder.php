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
                'description' => 'for daily use',
            ],
            [
                'material_name' => 'clinic gloves',
                'amount' => 500,
                'total_amount' => 2500,
                'expense_date' => '2024/04/04',
                'description' => 'for daily use',
            ],
            [
                'material_name' => 'dental syringe',
                'amount' => 150,
                'total_amount' => 1500,
                'expense_date' => '2024/04/04',
                'description' => 'surgical procedures',
            ],
            [
                'material_name' => 'anesthetic',
                'amount' => 250,
                'total_amount' => 5000,
                'expense_date' => '2024/04/04',
                'description' => 'pain management',
            ],
            [
                'material_name' => 'drill bit',
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


                    $table->string('name')->after('id');
            $table->string('description')->nullable()->after('name');
            $table->string('category')->nullable()->after('description');
            $table->decimal('width', 10, 2)->nullable()->after('category');
            $table->decimal('height', 10, 2)->nullable()->after('width');
            $table->decimal('depth', 10, 2)->nullable()->after('height');
            $table->boolean('is_sterile')->default(false)->after('depth');
            $table->date("expire_date")->nullable()->after('is_sterile');

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
