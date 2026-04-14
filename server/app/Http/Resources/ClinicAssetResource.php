<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicAssetResource extends JsonResource
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
            'assetName' => $this->asset_name,
            'description' => $this->description,
            'category' => $this->category,
            'width' => $this->width,
            'height' => $this->height,
            'depth' => $this->depth,
            'isSterile' => $this->is_sterile,
            'volume' => $this->volume,
            'amount' => $this->amount,
            'price' => $this->price,
            'totalAmount' => $this->total_amount,
            'dateOfPurchase' => $this->date_of_purchase,
            'status' => $this->status,
            'totalQuantityInStock' => $this->total_quantity,
            'purchasedByEmployeeId' => $this->purchasedByEmployee_id,
            'branchId' => $this->branch_id,
            'purchasedByEmployee' => new EmployeeResource($this->whenLoaded('Employee')),
            'branch' => $this->whenLoaded('Branch', function () {
                return [
                    'id' => $this->Branch->id,
                    'name' => $this->Branch->branch_name,
                ];
            }),
            'activePrice' => $this->whenLoaded('activePrice'),
            'prices' => ProductPriceResource::collection($this->whenLoaded('prices')),
            'inventoryStock' => InventoryStockResource::collection($this->whenLoaded('inventoryStock')),
        ];
    }
}
