<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Patient::insert([
            ['f_name' => 'John', 'l_name' => 'Doe', 'gender' => 'Male', 'phone' => '1234567890', 'blood_type' => 'O-', 'emergency_contact' => '000123456', 'registeration_date' => date('Y-m-d'), 'branch_id' => 1],
            ['f_name' => 'Jane', 'l_name' => 'Doe', 'gender' => 'Female', 'phone' => '9876543210', 'blood_type' => 'O+', 'emergency_contact' => '000123456', 'registeration_date' => date('Y-m-d'), 'branch_id' => 2],
        ]);
    }
}
