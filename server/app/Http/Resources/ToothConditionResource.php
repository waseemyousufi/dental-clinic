<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ToothConditionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'surfaces' => $this->surfaces ?? [], // Returns ['TOP', 'LEFT'] etc.
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            // Include the UI data (color/label) from the library
            'condition_library' => new ConditionLibraryResource($this->whenLoaded('conditionLibrary')),
        ];
    }
}
