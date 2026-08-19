<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceCategoryResource;
use App\Models\MarketplaceCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketplaceCategoryController extends Controller
{
    public function index() { return MarketplaceCategoryResource::collection(MarketplaceCategory::with('children')->orderBy('sort_order')->get()); }

    public function store(Request $request)
    {
        $category = MarketplaceCategory::create($this->validated($request));
        return (new MarketplaceCategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(Request $request, MarketplaceCategory $category)
    {
        $category->update($this->validated($request, $category));
        return new MarketplaceCategoryResource($category);
    }

    public function destroy(MarketplaceCategory $category)
    {
        abort_if($category->listings()->exists() || $category->children()->exists(), 422, 'Move or delete this category’s listings and child categories first.');
        $category->delete();
        return response()->json(null, 204);
    }

    private function validated(Request $request, ?MarketplaceCategory $category = null): array
    {
        return $request->validate([
            'parent_id' => ['nullable', 'exists:marketplace_categories,id', Rule::notIn([$category?->id])],
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('marketplace_categories')->ignore($category)],
            'description' => ['nullable', 'string', 'max:2000'], 'image_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['integer', 'min:0'], 'is_active' => ['boolean'],
        ]);
    }
}
