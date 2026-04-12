<?php

namespace Database\Seeders;

use App\Models\Allergy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AllergySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Allergy::insert([
            [
                'allergy_type' => 'surgical',
                'severity' => 'important',
                'description' => 'has allergy to numbening agent',
                'branch_id' => 1,
                'patient_id' => 1,
            ]
        ]);
    }
}
