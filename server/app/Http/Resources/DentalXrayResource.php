<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DentalXrayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "xray_type" => $this->xray_type,
            "xray_timestamp" => $this->xray_timestamp,
            "tooth_part" => $this->tooth_part,
            "side" => $this->side,
            "image_path" => $this->image_path,
            "diagnosis_notes" => $this->diagnosis_notes,
            "payment_status" => $this->payment_status,
            "results_summery" => $this->results_summery,
            "patient_id" => $this->patient_id,
            "requestedByEmployee_id" => $this->requestedByEmployee_id,
            "takenByEmployee_id" => $this->takenByEmployee_id,
            "patient" => $this->Patient->f_name . ' ' . $this->Patient->l_name,
            "requestedByEmployee" => $this->RequestedByEmployee->f_name . " " . $this->RequestedByEmployee->l_name,
            "takenByEmployee" => $this->TakenByEmployee->f_name . " " . $this->TakenByEmployee->l_name,
        ];
    }
}
