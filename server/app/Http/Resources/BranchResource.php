<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
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
            'branchName' => $this->branch_name,
            'region' => $this->region,
            'phone' => $this->phone,
            'ownerName' => $this->clinicOwner->name ?? null,
            'f_name' => $this->clinicOwner?->name ? (preg_split('/\s+/', trim($this->clinicOwner->name), -1, PREG_SPLIT_NO_EMPTY)[0] ?? '') : '',
            'l_name' => $this->clinicOwner?->name
                ? implode(' ', array_slice(preg_split('/\s+/', trim($this->clinicOwner->name), -1, PREG_SPLIT_NO_EMPTY) ?: [], 1))
                : '',
        ];
    }
}
