<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'parent_id' => $this->parent_id, 'name' => $this->name,
            'slug' => $this->slug, 'description' => $this->description, 'image_url' => $this->image_url,
            'sort_order' => $this->sort_order, 'is_active' => $this->is_active,
            'listings_count' => $this->whenCounted('listings'),
            'children' => MarketplaceCategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
