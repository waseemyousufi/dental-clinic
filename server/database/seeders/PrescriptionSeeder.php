<?php

namespace Database\Seeders;

use App\Models\Prescription;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrescriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Prescription::insert([
            // [
                // 'prescription_date' => '2024/04/04',
                // 'instructions' => '400mg',
                // 'employee_id' => 1,
                // 'branch_id' => 1,
                // 'patient_id' => 1
            // ]
            [
                'drug_name' => 'Paracetamol',
                'branch_id' => 1
            ]
        ]);
    }
}
