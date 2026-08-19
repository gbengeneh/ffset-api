<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceCategoryResource;
use App\Http\Resources\MarketplaceListingResource;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use Illuminate\Http\Request;
use App\Models\MarketplaceDeliveryZone;

class MarketplaceController extends Controller
{
    public function deliveryZones() { return MarketplaceDeliveryZone::where('is_active',true)->orderBy('fee')->get(['id','name','state','cities','fee','estimated_delivery']); }
    public function categories()
    {
        $categories = MarketplaceCategory::query()->whereNull('parent_id')->where('is_active', true)
            ->with(['children' => fn ($query) => $query->where('is_active', true)])
            ->withCount(['listings' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('sort_order')->get();
        return MarketplaceCategoryResource::collection($categories);
    }

    public function listings(Request $request)
    {
        $listings = MarketplaceListing::query()->where('status', 'active')->with(['category', 'images', 'vehicleDetails', 'fashionDetails', 'gadgetDetails', 'variants' => fn($query)=>$query->where('is_active',true)])
            ->when($request->query('category'), fn ($query, $slug) => $query->whereHas('category', fn ($q) => $q->where('slug', $slug)))
            ->when($request->query('q'), fn ($query, $search) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('short_description', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($request->boolean('featured'), fn ($query) => $query->where('is_featured', true))
            ->when($request->query('condition'), fn ($query, $condition) => $query->where('condition', $condition))
            ->when($request->query('min_price'), fn ($query, $min) => $query->where('price', '>=', $min))
            ->when($request->query('max_price'), fn ($query, $max) => $query->where('price', '<=', $max))
            ->when($request->query('sort') === 'price_asc', fn($query)=>$query->orderBy('price'))
            ->when($request->query('sort') === 'price_desc', fn($query)=>$query->orderByDesc('price'))
            ->when(!in_array($request->query('sort'),['price_asc','price_desc'],true), fn($query)=>$query->orderByDesc('is_featured')->orderByDesc('published_at'))
            ->paginate(min($request->integer('per_page', 24), 60));
        return MarketplaceListingResource::collection($listings);
    }

    public function show(MarketplaceListing $listing)
    {
        abort_unless($listing->status === 'active', 404);
        return new MarketplaceListingResource($listing->load(['category', 'images', 'vehicleDetails', 'fashionDetails', 'gadgetDetails', 'variants' => fn($query)=>$query->where('is_active',true)]));
    }
}
