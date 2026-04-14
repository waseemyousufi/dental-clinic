<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShelfResource extends JsonResource
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
            'shelfName' => $this->shelf_name,
            'shelfType' => $this->shelf_type,
            'accessPin' => $this->access_pin,
            'totalCapacityCm3' => $this->total_capacity_cm3,
            'categoryRestriction' => $this->category_restriction,
            'usedCapacity' => $this->used_capacity,
            'availableCapacity' => $this->available_capacity,
            'inventoryStock' => InventoryStockResource::collection($this->whenLoaded('inventoryStock')),
        ];
    }
}
