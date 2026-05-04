<?php

namespace Database\Seeders;

use App\Models\Treatment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TreatmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Treatment::insert([
            [
                'treatment_type' => 'Crown',
                'diagnosis' => 'Damged Tooth',
                'treatment_date' => '2026/03/03',
                'duration' => '45m',
                'cost' => 1500,
                'description' => 'Treatment done successfully',
                'patient_id' => 1,
                // 'xray_id' => 1,
                'branch_id' => 1,
                'treatment_plan_id' => 1,
            ]
        ]);
    }
}
