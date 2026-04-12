<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds
     */
    public function run(): void
    {
        Employee::insert(
            [
                [
                    'f_name' => 'jhon',
                    'l_name' => 'brown',
                    'qualification' => 'doctor surgeon',
                    'speciality' => 'dental',
                    'gender' => 'male',
                    'medical_license_number' => '343232',
                    'hire_date' => '2006/06/06',
                    'work_start_time' => '12:00',
                    'work_end_time' => '12:00',
                    'position_id' => 1,
                    'branch_id' => 1,
                    'user_id' => 1,
                ],
                [
                    'f_name' => 'micheal',
                    'l_name' => 'brown',
                    'qualification' => 'doctor surgeon',
                    'speciality' => 'dental',
                    'gender' => 'male',
                    'medical_license_number' => '343232',
                    'hire_date' => '2006/06/06',
                    'work_start_time' => '12:00',
                    'work_end_time' => '12:00',
                    'position_id' => 2,
                    'branch_id' => 2,
                    'user_id' => 2,
                ]
            ],
        );
    }
}
