<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ToothConditionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tooth_id' => $this->tooth_id, // CRITICAL: This connects it to the tooth object
            'surfaces' => $this->surfaces ?? [],
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'condition_library' => new ConditionLibraryResource($this->whenLoaded('conditionLibrary')),
            // Optional: If you want to be extra safe for mapping
            'fdi_code' => $this->tooth->fdi_code ?? null,
        ];
    }
}
