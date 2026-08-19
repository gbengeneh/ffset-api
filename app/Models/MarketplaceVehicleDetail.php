<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceVehicleDetail extends Model
{
    protected $fillable = ['listing_id', 'make', 'model', 'year', 'mileage', 'transmission', 'fuel_type', 'color', 'vin', 'features'];
    protected function casts(): array { return ['year' => 'integer', 'mileage' => 'integer', 'features' => 'array']; }
    public function listing(): BelongsTo { return $this->belongsTo(MarketplaceListing::class, 'listing_id'); }
}
