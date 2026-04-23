<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ToothResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'fdi_code' => $this->fdi_code,
            // Only return active conditions to keep the payload light
            'active_conditions' => ToothConditionResource::collection(
                $this->whenLoaded('activeConditions')
            ),
        ];
    }
}
