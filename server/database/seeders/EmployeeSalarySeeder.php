<?php

namespace Database\Seeders;

use App\Models\EmployeeSalary;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeSalarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmployeeSalary::insert([
            [
                'branch_id' => 1,
                'employee_id' => 1,
                'salary_month' => 'jun 2025',
                'amount' => 10000,
                'bonus' => 1500,
                'total_amount' => 11500,
                'remark' => 'paid',
                'paidByAccountTransaction_id' => 4,
            ]
        ]);
    }
}
