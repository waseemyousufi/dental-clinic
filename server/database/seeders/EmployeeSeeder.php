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
                    'f_name' => 'Dentist',
                    'l_name' => 'Professional',
                    'qualification' => 'doctor surgeon',
                    'speciality' => 'dental',
                    'gender' => 'male',
                    'medical_license_number' => 'DENT-001',
                    'hire_date' => '2020/01/01',
                    'work_start_time' => '08:00',
                    'work_end_time' => '16:00',
                    'position_id' => 1, // doctor
                    'branch_id' => 1,
                    'user_id' => 1,
                ],
                [
                    'f_name' => 'Big',
                    'l_name' => 'Boss',
                    'qualification' => 'Administrator',
                    'speciality' => 'Management',
                    'gender' => 'male',
                    'medical_license_number' => 'ADM-001',
                    'hire_date' => '2019/01/01',
                    'work_start_time' => '09:00',
                    'work_end_time' => '17:00',
                    'position_id' => 4, // admin
                    'branch_id' => null,
                    'user_id' => 2,
                ],
                [
                    'f_name' => 'Receptionist',
                    'l_name' => 'Staff',
                    'qualification' => 'Diploma',
                    'speciality' => 'Administration',
                    'gender' => 'female',
                    'medical_license_number' => 'REC-001',
                    'hire_date' => '2021/01/01',
                    'work_start_time' => '08:00',
                    'work_end_time' => '16:00',
                    'position_id' => 3, // receptionist
                    'branch_id' => 1,
                    'user_id' => 3,
                ]
            ],
        );
    }
}
