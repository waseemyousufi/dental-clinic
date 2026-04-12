<?php

namespace Database\Seeders;

use App\Models\DentalXray;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DentalXraySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DentalXray::insert([
            [
                'xray_type' => 'Periaical',
                'xray_timestamp' => '2026/03/01 10:30',
                'tooth_part' => 'Molar',
                'side' => 'left',
                'image_path' => '/img/img1.jpg',
                'diagnosis_notes' => 'Damaged tooth',
                'payment_status' => 'Included',
                'results_summery' => 'Crown required',
                'patient_id' => 1,
                'requestedByEmployee_id' => 1,
                'takenByEmployee_id' => 1,
                'branch_id' => 1,
            ]
        ]);
    }
}
