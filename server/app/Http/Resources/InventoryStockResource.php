<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStockResource extends JsonResource
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
            'stockableId' => $this->stockable_id,
            'stockableType' => $this->stockable_type,
            'stockable' => $this->stockable_type === 'App\Models\ClinicMaterial'
                ? new ClinicMaterialResource($this->whenLoaded('stockable'))
                : new ClinicAssetResource($this->whenLoaded('stockable')),
            'shelfId' => $this->shelf_id,
            'shelf' => new ShelfResource($this->whenLoaded('shelf')),
            'quantity' => $this->quantity,
            'expiryDate' => $this->expiry_date?->format('Y-m-d'),
            'batchNumber' => $this->batch_number,
            'status' => $this->status,
            'isExpired' => $this->is_expired,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
