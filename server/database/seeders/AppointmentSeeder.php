<?php

namespace Database\Seeders;

use App\Models\Appointment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Appointment::insert([
            [
                'appointment_timestamp' => '2026/03/03 10:30',
                'status' => 'Completed',
                'description' => 'cavity filling',
                'branch_id' => 1,
                'appointment_cost' => 100
            ]
        ]);
    }
}
