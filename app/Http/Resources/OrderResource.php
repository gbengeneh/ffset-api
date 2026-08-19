<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_code' => $this->reference_code,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'fulfillment_type' => $this->fulfillment_type,
            'delivery_address' => $this->delivery_address,
            'notes' => $this->notes,
            'status' => $this->status,
            'sale' => $this->whenLoaded('sale'),
            'created_at' => $this->created_at,
        ];
    }
}
