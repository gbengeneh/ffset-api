<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    protected $fillable = [
        'make',
        'model',
        'year',
        'price',
        'deposit_amount',
        'mileage',
        'condition',
        'transmission',
        'fuel_type',
        'color',
        'vin',
        'description',
        'features',
        'status',
        'deposit_product_id',
        'marketplace_listing_id',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'price' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'mileage' => 'integer',
            'features' => 'array',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(CarImage::class)->orderBy('sort_order');
    }

    public function depositProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'deposit_product_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CarOrder::class);
    }
}
