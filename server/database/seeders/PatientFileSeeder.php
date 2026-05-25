<?php

namespace Database\Seeders;

use App\Models\DentalXray;
use App\Models\PatientFile;
use Illuminate\Database\Seeder;

class PatientFileSeeder extends Seeder
{
    /**
     * Run the seed.
     *
     * @return void
     */
    public function run()
    {
        $xray = DentalXray::where('branch_id', 1)->first();

        PatientFile::create(
            [
                'branch_id' => 1,
                'diagnosis' => 'this is good',
                'patient_id' => 2,
                'employee_id' => 2,
                'appointmentDate_id' => 1,
                'treatment_id' => 1,
                'allergy_id' => 1,
                'diagnosis_notes' => $xray->diagnosis_notes,
            ]
        );
    }
}
