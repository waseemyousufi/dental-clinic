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
                'name' => 'Dental Chair',
                'asset_name' => 'dental chair',
                'description' => 'Standard dental chair for patients',
                'category' => 'furniture',
                'width' => 100.00,
                'height' => 120.00,
                'depth' => 80.00,
                'is_sterile' => false,
                'amount' => 2,
                'price' => 5000,
                'total_amount' => 10000,
                'date_of_purchase' => '2024/04/04',
                'status' => 'active',
                'purchasedByEmployee_id' => 1,
                'branch_id' => 1,
            ],
            [
                'name' => 'X-Ray Machine',
                'asset_name' => 'x-ray machine',
                'description' => 'Digital dental x-ray machine',
                'category' => 'device',
                'width' => 50.00,
                'height' => 70.00,
                'depth' => 40.00,
                'is_sterile' => false,
                'amount' => 1,
                'price' => 15000,
                'total_amount' => 15000,
                'date_of_purchase' => '2024/04/04',
                'status' => 'active',
                'purchasedByEmployee_id' => 1,
                'branch_id' => 1,
            ],
            [
                'name' => 'Desk',
                'asset_name' => 'desk',
                'description' => 'Office desk for reception',
                'category' => 'furniture',
                'width' => 120.00,
                'height' => 75.00,
                'depth' => 60.00,
                'is_sterile' => false,
                'amount' => 7,
                'price' => 1500,
                'total_amount' => 10500,
                'date_of_purchase' => '2024/04/04',
                'status' => 'active',
                'purchasedByEmployee_id' => 1,
                'branch_id' => 1,
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
