<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::insert([
            [
                'account_name' => 'patient payment',
                'account_type' => 'income',
                'total_amount' => 10000,
                'status' => 'active',
                'branch_id' => 1
            ],
            [
                'account_name' => 'material expenses',
                'account_type' => 'outlay',
                'total_amount' => 12000,
                'status' => 'active',
                'branch_id' => 1
            ],
            [
                'account_name' => 'patient payment',
                'account_type' => 'income',
                'total_amount' => 10000,
                'status' => 'active',
                'branch_id' => 2
            ]
        ]);
    }
}
