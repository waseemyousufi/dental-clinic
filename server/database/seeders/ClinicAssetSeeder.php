<?php

namespace Database\Seeders;

use App\Models\ClinicAsset;
use App\Models\ProductPrice;
use App\Models\InventoryStock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClinicAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assets = [
            [
                'asset_name' => 'dental chair',
                'amount' => 2,
                'price' => 5000,
                'total_amount' => 10000,
                'date_of_purchase' => '2024/04/04',
                'status' => 'active',
                'purchasedByEmployee_id' => 1,
                'branch_id' => 1,
                'category' => 'Medical Equipment',
            ],
            [
                'asset_name' => 'x-ray machine',
                'amount' => 1,
                'price' => 15000,
                'total_amount' => 15000,
                'date_of_purchase' => '2024/04/04',
                'status' => 'active',
                'purchasedByEmployee_id' => 1,
                'branch_id' => 1,
                'category' => 'Medical Equipment',
            ],
            [
                'asset_name' => 'desk',
                'amount' => 7,
                'price' => 1500,
                'total_amount' => 10500,
                'date_of_purchase' => '2024/04/04',
                'status' => 'active',
                'purchasedByEmployee_id' => 1,
                'branch_id' => 1,
                'category' => 'Furniture',
            ],
        ];

        $createdAssets = [];
        foreach ($assets as $assetData) {
            $asset = ClinicAsset::create($assetData);
            $createdAssets[] = $asset;

            // Create active price
            ProductPrice::create([
                'pricable_id' => $asset->id,
                'pricable_type' => ClinicAsset::class,
                'price' => $asset->price,
                'effective_from' => now(),
                'is_active' => true,
            ]);
        }
    }
}
