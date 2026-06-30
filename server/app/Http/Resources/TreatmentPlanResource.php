<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $procedure = $this->procedure()->first();

        if($request->query('abbreviated') === 'true') {
            return [
                'id' => $this->id,
                'patient_id' => $this->patient_id,
                'procedure_id' => $this->procedure_id,
                'procedure_name' => $procedure ? $procedure->name : null,
                'branch_id' => $this->branch_id,
                'createdBy_id' => $this->createdBy_id,
                'total_estimated_cost' => $this->total_estimated_cost,
                // 'total_amount_paid' => $this->total_amount_paid,
                'status' => $this->status,
                // 'appointments_needed' => $this->appointments_needed,
                'start_date' => $this->start_date,
                'is_accepted' => $this->isAccepted(),
            ];
        }


        return [
            'id' => $this->id,

            // Foreign keys
            'patient_id' => $this->patient_id,
            'procedure_id' => $this->procedure_id,
            'branch_id' => $this->branch_id,
            'createdBy_id' => $this->createdBy_id,

            // Core fields
            'total_estimated_cost' => $this->total_estimated_cost,
            'total_amount_paid' => $this->total_amount_paid,
            'status' => $this->status,
            'appointments_needed' => $this->appointments_needed,
            'start_date' => $this->start_date,

            // Computed
            'is_accepted' => $this->isAccepted(),

            // Relationships (only if loaded)
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'appointments' => AppointmentResource::collection($this->whenLoaded('appointments')),
            'procedure' => new ProcedureResource($procedure),
            'createdBy' => new EmployeeResource($this->whenLoaded('createdBy')),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
