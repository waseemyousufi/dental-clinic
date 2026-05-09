<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $patient = $this->patients()->orderByPivot('created_at', 'desc')->first();
        $employee = $this->employees()->orderByPivot('created_at', 'desc')->first();

        return [
            'id' => $this->id,
            'appointment_timestamp' => $this->appointment_timestamp,
            'status' => $this->status,
            'description' => $this->description,
            'treatment_plan_id' => $this->treatment_plan_id,
            "patient" => $patient?->f_name . ' ' . $patient?->l_name,
            "employee" => $employee?->f_name . ' ' . $employee?->l_name,
            'patientId' => $patient?->id,
            "employeeId" => $employee?->id,
            "clinical_notes" => $this->clinical_notes,
            "appointment_cost" => $this->appointment_cost
        ];
    }
}
