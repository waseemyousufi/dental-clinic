<?php

namespace Database\Seeders;

use App\Models\AccountTransaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {  
        AccountTransaction::insert([
            [
                'transaction_type' => 'in',
                'amount' => 1000,
                'transaction_date' => '2024/04/04',
                'reference_type' => 'patient',
                'description' => 'collected from patients',
                'recorded_by_employee_id' => 1,
                'account_id' => 1,
                'branch_id' => 1,
            ],
            [
                'transaction_type' => 'in',
                'amount' => 1500,
                'transaction_date' => '2024/04/04',
                'reference_type' => 'patient',
                'description' => 'collected from patients',
                'recorded_by_employee_id' => 2,
                'account_id' => 3,
                'branch_id' => 2,
            ],
            [
                'transaction_type' => 'out',
                'amount' => 2500,
                'transaction_date' => '2024/04/04',
                'reference_type' => 'patient',
                'description' => 'clinic material expenses',
                'recorded_by_employee_id' => 1,
                'account_id' => 2,
                'branch_id' => 1,
            ],
            [
                'transaction_type' => 'out',
                'amount' => 2500,
                'transaction_date' => '2024/04/04',
                'reference_type' => 'patient',
                'description' => 'paid employee salary',
                'recorded_by_employee_id' => 1,
                'account_id' => 2,
                'branch_id' => 1,
            ]
        ]);
    }
}
