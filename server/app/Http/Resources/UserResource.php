<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // EmployeeResource expects these relations to exist on the linked employee.
        $this->resource->loadMissing([
            'employee.Position',
            'employee.Branch',
            'employee.experience',
            'employee.user',
        ]);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile_image_url' => $this->profile_image_path
                ? Storage::disk('public')->url($this->profile_image_path)
                : "https://ui-avatars.com/api/?name=" . urlencode($this->name),
            'employee' => $this->employee ? new EmployeeResource($this->employee) : null,
        ];
    }
}
