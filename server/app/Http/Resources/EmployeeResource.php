<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        if ($request->query('abr')) {
            return [
                'id' => $this->id,
                'name' => $this->f_name . ' ' . $this->l_name,
            ];
        }

        $branch = $this->Branch;

        $isAdmin = ($this->Position?->position_title === 'admin');

        if ($isAdmin) {
           $branch = null; // hide branch info for admins
        }

        return [
            'id' => $this->id,
            'name' => $this->f_name . ' ' . $this->l_name,
            'fName' => $this->f_name,
            'lName' => $this->l_name,
            'position' => $this->Position->position_title,
            'gender' => $this->gender,
            'speciality' => $this->speciality,
            'qualification' => $this->qualification,
            'email' => $this->user->email,
            'hireDate' => $this->hire_date,
            'midLicenseNum' => $this->medical_license_number,
            'workStartTime' => $this->work_start_time,
            'workEndTime' => $this->work_end_time,
            'positionId' => $this->position_id,
            'branchId' => $isAdmin ? null : $this->branch_id,
            'experience' => new EmployeeExperienceResource($this->experience),
            'profile_image_url' => ($this->user && $this->user->profile_image_path)
                ? Storage::disk('public')->url($this->user->profile_image_path)
                : "https://ui-avatars.com/api/?name=" . urlencode($this->f_name . ' ' . $this->l_name),
        ];
    }
}
