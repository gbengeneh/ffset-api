<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGalleryItemRequest;
use App\Models\GalleryItem;

class GalleryController extends Controller
{
    public function index()
    {
        return response()->json(GalleryItem::orderBy('id')->get());
    }

    public function store(StoreGalleryItemRequest $request)
    {
        return response()->json(GalleryItem::create($request->validated()), 201);
    }

    public function update(StoreGalleryItemRequest $request, GalleryItem $galleryItem)
    {
        $galleryItem->update($request->validated());

        return response()->json($galleryItem);
    }

    public function destroy(GalleryItem $galleryItem)
    {
        $galleryItem->delete();

        return response()->json(null, 204);
    }
}
