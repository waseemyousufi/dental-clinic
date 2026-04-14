<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicMaterialResource extends JsonResource
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
            'materialName' => $this->material_name,
            'description' => $this->description,
            'category' => $this->category,
            'width' => $this->width,
            'height' => $this->height,
            'depth' => $this->depth,
            'isSterile' => $this->is_sterile,
            'volume' => $this->volume,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'totalAmount' => $this->total_amount,
            'expenseDate' => $this->expense_date,
            'totalQuantityInStock' => $this->total_quantity,
            'activePrice' => $this->whenLoaded('activePrice'),
            'prices' => ProductPriceResource::collection($this->whenLoaded('prices')),
            'inventoryStock' => InventoryStockResource::collection($this->whenLoaded('inventoryStock')),
            'branches' => $this->branches->map(fn($branch) => [
                'id' => $branch->id,
                'name' => $branch->branch_name,
            ]),
            'accountTransactions' => $this->accountTransactions->map(fn($transaction) => [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
            ]),
        ];
    }
}
