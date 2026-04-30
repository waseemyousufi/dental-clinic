<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
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
            'contactPersonName' => $this->contact_person_name,
            'organizationName' => $this->organization_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'businessId' => $this->business_id,
            'ordersCount' => $this->whenCounted('orders'),
            'items' => ItemResource::collection($this->whenLoaded('items')),
            'itemsCount' => $this->whenCounted('items'),
            'address' => $this->address,
            'notes' => $this->notes,
        ];
    }
}
