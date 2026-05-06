<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcedureResource extends JsonResource
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

            // Core fields
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,

            // Pricing
            'base_price' => (float) $this->base_price,
            'dentist_commission' => (float) $this->dentist_commission,
            'assistant_commission' => (float) $this->assistant_commission,

            // Status
            'is_active' => (bool) $this->is_active,

            // Relationships
            'inventory_requirements' => ProcedureInventoryResource::collection(
                $this->whenLoaded('inventoryRequirements')
            ),

            'stocks' => $this->whenLoaded('stocks', function () {
                return $this->stocks->map(function ($stock) {
                    return [
                        'id' => $stock->id,
                        'name' => $stock->name ?? null, // adjust if different

                        // Pivot data
                        'unit_count' => $stock->pivot->unit_count,
                        'is_optional' => (bool) $stock->pivot->is_optional,
                    ];
                });
            }),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
