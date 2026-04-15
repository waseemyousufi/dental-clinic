<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
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
            'name' => $this->name,
            'category' => $this->category,
            'materials' => $this->materials,
            'description' => $this->description,
            'trackStock' => $this->track_stock,
            'requiresBatch' => $this->requires_batch,
            'requiresExpiry' => $this->requires_expiry,
            'isConsumable' => $this->is_consumable,
            'totalQuantityInStock' => $this->when(
                $this->relationLoaded('inventoryStock'),
                fn() => $this->inventoryStock->sum('quantity'),
                fn() => $this->inventoryStock()->sum('quantity')
            ),
            'activePrice' => $this->whenLoaded('activePrice'),
            'suppliers' => $this->whenLoaded('suppliers', fn() => $this->suppliers->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->organization_name,
            ])),
        ];
    }
}
