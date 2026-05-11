<?php
namespace Database\Seeders;

use App\Models\TreatmentPlan;
use App\Models\Procedure;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Database\Seeder;

class TreatmentPlanSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing IDs to ensure foreign key constraints pass
        $patientId = Patient::first()?->id ?? 1;
        $appointmentId = Appointment::first()?->id ?? 1;

        // Fetch specific procedures to calculate estimated costs
        $filling = Procedure::where('slug', 'composite-filling-1')->first();
        $scaling = Procedure::where('slug', 'scaling-polishing')->first();

        $plans = [
            [
                'patient_id' => $patientId,
                'procedure_id' => $filling?->id ?? 1,
                'branch_id' => 1,
                'total_estimated_cost' => $filling?->base_price ?? 1500,
                'total_amount_paid' => 100,
                'appointments_needed' => 4,
                'start_date' => '2026/07/07',
                'status' => 'proposed',
            ],
            // [
            //     // Note: If patient_id is unique in your migration,
            //     // you can only have one plan per patient.
            //     'patient_id' => 2,
            //     'appointment_id' => 2,
            //     'procedure_id' => $scaling?->id ?? 1,
            //     'total_estimated_cost' => $scaling?->base_price ?? 800,
            //     'status' => 'accepted',
            // ],
        ];

        foreach ($plans as $plan) {
            TreatmentPlan::create($plan);
        }
    }
}
