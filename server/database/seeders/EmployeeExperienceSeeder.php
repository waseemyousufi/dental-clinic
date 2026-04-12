<?php

namespace Database\Seeders;

use App\Models\EmployeeExperience;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeExperienceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmployeeExperience::insert([
            [
                'workplace' => 'Sama Dental Clinic',
                'position' => 'Doctor',
                'total_amount' => 13000,
                'employee_id' => 1,
            ]
        ]);
    }
}
