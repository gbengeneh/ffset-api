<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMarketplaceListingRequest;
use App\Http\Requests\UploadMarketplaceListingImageRequest;
use App\Http\Resources\MarketplaceListingResource;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketplaceListingController extends Controller
{
    public function index(Request $request)
    {
        return MarketplaceListingResource::collection(MarketplaceListing::query()->with(['category', 'images', 'vehicleDetails', 'fashionDetails', 'gadgetDetails', 'variants'])
            ->when($request->query('category'), fn ($query, $slug) => $query->whereHas('category', fn ($q) => $q->where('slug', $slug)))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()->paginate(min($request->integer('per_page', 30), 100)));
    }

    public function store(StoreMarketplaceListingRequest $request)
    {
        $listing = DB::transaction(fn () => $this->persist(new MarketplaceListing, $request->validated()));
        return (new MarketplaceListingResource($listing->load($this->relations())))->response()->setStatusCode(201);
    }

    public function update(StoreMarketplaceListingRequest $request, MarketplaceListing $listing)
    {
        $listing = DB::transaction(fn () => $this->persist($listing, $request->validated()));
        return new MarketplaceListingResource($listing->load($this->relations()));
    }

    public function destroy(MarketplaceListing $listing)
    {
        $listing->delete();
        return response()->json(null, 204);
    }

    public function uploadImage(UploadMarketplaceListingImageRequest $request, MarketplaceListing $listing)
    {
        $disk = config('filesystems.default') === 'local' ? 'public' : config('filesystems.default');
        $path = $request->file('image')->store('marketplace', $disk);
        $listing->images()->create([
            'image_url' => Storage::disk($disk)->url($path), 'storage_disk' => $disk, 'storage_path' => $path, 'alt_text' => $request->validated('alt_text'),
            'sort_order' => ((int) $listing->images()->max('sort_order')) + 1,
        ]);
        return new MarketplaceListingResource($listing->fresh()->load($this->relations()));
    }

    public function deleteImage(MarketplaceListing $listing, MarketplaceListingImage $image)
    {
        abort_unless($image->listing_id === $listing->id, 404);
        if ($image->storage_disk && $image->storage_path) Storage::disk($image->storage_disk)->delete($image->storage_path);
        else { $storagePath = parse_url($image->image_url, PHP_URL_PATH); if ($storagePath && Str::contains($storagePath, '/storage/')) Storage::disk('public')->delete(Str::after($storagePath, '/storage/')); }
        $image->delete();
        return new MarketplaceListingResource($listing->fresh()->load($this->relations()));
    }

    private function persist(MarketplaceListing $listing, array $data): MarketplaceListing
    {
        $vehicle = $data['vehicle'] ?? null;
        $fashion = $data['fashion'] ?? null; $gadget = $data['gadget'] ?? null;
        unset($data['vehicle'], $data['fashion'], $data['gadget']);
        $listing->fill($data)->save();
        if ($vehicle) $listing->vehicleDetails()->updateOrCreate([], $vehicle);
        else $listing->vehicleDetails()->delete();
        if ($fashion) $listing->fashionDetails()->updateOrCreate([], $fashion);
        else $listing->fashionDetails()->delete();
        if ($gadget) $listing->gadgetDetails()->updateOrCreate([], $gadget);
        else $listing->gadgetDetails()->delete();
        return $listing;
    }

    private function relations(): array { return ['category', 'images', 'vehicleDetails', 'fashionDetails', 'gadgetDetails', 'variants']; }
}
