<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'sku' => $this->sku,
            'short_description' => $this->short_description, 'description' => $this->description,
            'price' => $this->price, 'compare_at_price' => $this->compare_at_price,
            'deposit_amount' => $this->deposit_amount, 'condition' => $this->condition,
            'status' => $this->status, 'stock_quantity' => $this->stock_quantity,
            'is_featured' => $this->is_featured, 'attributes' => $this->attributes ?? [],
            'published_at' => $this->published_at, 'created_at' => $this->created_at,
            'category' => new MarketplaceCategoryResource($this->whenLoaded('category')),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id, 'image_url' => $image->image_url,
                'alt_text' => $image->alt_text, 'sort_order' => $image->sort_order,
            ])),
            'details' => $this->whenLoaded('vehicleDetails', fn () => $this->vehicleDetails ? [
                'type' => 'vehicle', 'make' => $this->vehicleDetails->make, 'model' => $this->vehicleDetails->model,
                'year' => $this->vehicleDetails->year, 'mileage' => $this->vehicleDetails->mileage,
                'transmission' => $this->vehicleDetails->transmission, 'fuel_type' => $this->vehicleDetails->fuel_type,
                'color' => $this->vehicleDetails->color, 'vin' => $this->vehicleDetails->vin,
                'features' => $this->vehicleDetails->features ?? [],
            ] : null),
            'fashion_details' => $this->whenLoaded('fashionDetails', fn () => $this->fashionDetails ? [
                'brand' => $this->fashionDetails->brand, 'gender' => $this->fashionDetails->gender,
                'genders' => $this->fashionDetails->genders ?? ($this->fashionDetails->gender ? [$this->fashionDetails->gender] : []),
                'material' => $this->fashionDetails->material, 'sizes' => $this->fashionDetails->sizes ?? [],
                'colors' => $this->fashionDetails->colors ?? [],
            ] : null),
            'gadget_details' => $this->whenLoaded('gadgetDetails', fn () => $this->gadgetDetails ? [
                'brand' => $this->gadgetDetails->brand, 'model' => $this->gadgetDetails->model,
                'storage' => $this->gadgetDetails->storage, 'memory' => $this->gadgetDetails->memory,
                'warranty' => $this->gadgetDetails->warranty, 'specifications' => $this->gadgetDetails->specifications ?? [],
            ] : null),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($variant) => [
                'id' => $variant->id, 'sku' => $variant->sku, 'options' => $variant->options,
                'price' => $variant->price, 'stock_quantity' => $variant->stock_quantity, 'is_active' => $variant->is_active,
            ])),
        ];
    }
}
