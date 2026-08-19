<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MarketplaceGadgetDetail extends Model
{
    protected $fillable = ['listing_id', 'brand', 'model', 'storage', 'memory', 'warranty', 'specifications'];
    protected function casts(): array { return ['specifications' => 'array']; }
    public function listing(): BelongsTo { return $this->belongsTo(MarketplaceListing::class, 'listing_id'); }
}
