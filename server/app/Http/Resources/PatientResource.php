<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fName' => $this->f_name,
            'lName' => $this->l_name,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'bloodType' => $this->blood_type,
            'emgContact' => $this->emergency_contact,
            'totalAmountDue' => $this->total_amount_due,
            'registerationDate' => $this->registeration_date,
        ];
    }
}
