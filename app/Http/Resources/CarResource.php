<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'price' => $this->price,
            'deposit_amount' => $this->deposit_amount,
            'mileage' => $this->mileage,
            'condition' => $this->condition,
            'transmission' => $this->transmission,
            'fuel_type' => $this->fuel_type,
            'color' => $this->color,
            'vin' => $this->vin,
            'description' => $this->description,
            'features' => $this->features ?? [],
            'status' => $this->status,
            'images' => CarImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at,
        ];
    }
}
