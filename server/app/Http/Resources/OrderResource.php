<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'supplierName' => $this->supplier_name,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'date' => $this->date?->format('Y-m-d'),
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'itemsCount' => $this->whenCounted('items'),
        ];
    }
}
