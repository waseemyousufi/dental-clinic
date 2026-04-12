<?php

namespace Database\Seeders;

use App\Models\ClinicAsset;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClinicAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClinicAsset::insert([
            [
                'asset_name' => 'desk',
                'category' => 'furniture',
                'amount' => 7,
                'price' => 1500,
                'total_amount' => 10500,
                'date_of_purchase' => '2024/04/04',
                'status' => 'active',
                'purchasedByEmployee_id' => 1,
                'branch_id' => 1,
            ],
            [
                'asset_name' => 'desk',
                'category' => 'furniture',
                'amount' => 7,
                'price' => 1500,
                'total_amount' => 10500,
                'date_of_purchase' => '2024/04/04',
                'status' => 'active',
                'purchasedByEmployee_id' => 1,
                'branch_id' => 1,
            ]
        ]);
    }
}
