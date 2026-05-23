<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Branch::insert(
            [
                [
                    'branch_name' => 'clinic bahar',
                    'region' => 'kabul, Afghanistan',
                    'phone' => '0735224242',
                    'clinic_owner_id' => 1
                ],
                [
                    'branch_name' => 'dandan clinic',
                    'region' => 'kabul, Afghanistan',
                    'phone' => '0735244242',
                    'clinic_owner_id' => 1
                ]
            ]
        );
    }
}
