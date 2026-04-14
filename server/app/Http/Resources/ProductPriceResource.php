<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPriceResource extends JsonResource
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
            'pricableId' => $this->pricable_id,
            'pricableType' => $this->pricable_type,
            'price' => $this->price,
            'effectiveFrom' => $this->effective_from?->format('Y-m-d H:i:s'),
            'isActive' => $this->is_active,
            'discountPercentage' => $this->discount_percentage,
            'currencyExchangeRate' => $this->currency_exchange_rate,
            'finalPrice' => $this->final_price,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
