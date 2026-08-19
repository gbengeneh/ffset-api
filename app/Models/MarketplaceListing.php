<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketplaceListing extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'short_description', 'description', 'price',
        'compare_at_price', 'deposit_amount', 'condition', 'status', 'stock_quantity',
        'is_featured', 'attributes', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2', 'compare_at_price' => 'decimal:2', 'deposit_amount' => 'decimal:2',
            'stock_quantity' => 'integer', 'is_featured' => 'boolean', 'attributes' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo { return $this->belongsTo(MarketplaceCategory::class, 'category_id'); }
    public function images(): HasMany { return $this->hasMany(MarketplaceListingImage::class, 'listing_id')->orderBy('sort_order'); }
    public function vehicleDetails(): HasOne { return $this->hasOne(MarketplaceVehicleDetail::class, 'listing_id'); }
    public function fashionDetails(): HasOne { return $this->hasOne(MarketplaceFashionDetail::class, 'listing_id'); }
    public function gadgetDetails(): HasOne { return $this->hasOne(MarketplaceGadgetDetail::class, 'listing_id'); }
    public function variants(): HasMany { return $this->hasMany(MarketplaceListingVariant::class, 'listing_id')->orderBy('id'); }
}
