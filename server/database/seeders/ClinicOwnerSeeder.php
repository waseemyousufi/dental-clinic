<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClinicOwner;

class ClinicOwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClinicOwner::create([
            'name' => 'John Doe',
            'phone' => '123-456-7890',
            'email' => 'mail@mail.com',
            'total_amount_due' => 1000.00,
            'total_amount_paid' => 500.00,
        ]);
    }
}
