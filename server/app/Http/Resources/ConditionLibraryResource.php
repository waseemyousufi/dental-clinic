<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConditionLibraryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'label' => $this->label, // e.g., "Caries"
            'slug' => $this->slug,   // e.g., "diagnostic"
            'ui_color' => $this->ui_color, // e.g., "#ff4d4f"
            'svg_path' => $this->svg_path, // e.g., "M10 10 H 90 V 90 H 10 Z"
        ];
    }
}
