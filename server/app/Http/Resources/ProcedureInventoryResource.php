<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcedureInventoryResource extends JsonResource
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

            // Foreign keys
            'procedure_id' => $this->procedure_id,
            'inventory_item_id' => $this->inventory_item_id,

            // Core fields
            'unit_count' => (float) $this->unit_count,
            'is_optional' => (bool) $this->is_optional,

            // Relationships (only if loaded)
            'procedure' => new ProcedureResource($this->whenLoaded('procedure')),

            'stock' => new InventoryStockResource($this->whenLoaded('stock')),

            // Timestamps (if your table has them)
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null,
        ];
    }
}
