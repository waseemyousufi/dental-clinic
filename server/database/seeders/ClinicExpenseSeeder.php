<?php

namespace Database\Seeders;

use App\Models\ClinicExpense;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClinicExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClinicExpense::insert([
            [
                'expense_category' => 'gas',
                'unit' => '10kg',
                'amount' => 600,
                'expense_date' => '2024/04/04',
                'description' => 'filling the gas tank',
                'paidByEmployee_id' => 1,
                'branch_id' => 1,
            ]
        ]);
    }
}
