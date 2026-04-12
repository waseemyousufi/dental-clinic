<?php

namespace Database\Seeders;

use App\Models\Reception;
use Illuminate\Database\Seeder;

class ReceptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Reception::insert([
            [
                'status' => 'scheduled',
                'fee' => 150,
                'admission_timestamp' => '2024/04/04',
                'employee_id' => 1,
                'patient_id' =>   1
            ],
            [
                'status' => 'scheduled',
                'fee' => 150,
                'admission_timestamp' => '2024/04/04',
                'employee_id' => 2,
                'patient_id' => 2
            ],
        ]);
    }
}
