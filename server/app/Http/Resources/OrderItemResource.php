<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'orderId' => $this->order_id,
            'itemId' => $this->item_id,
            'item' => new ClinicMaterialResource($this->whenLoaded('item')),
            'quantity' => $this->quantity,
            'unitPrice' => $this->unit_price,
            'totalPrice' => $this->total_price,
        ];
    }
}
