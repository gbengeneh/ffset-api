<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MarketplaceFashionDetail extends Model
{
    protected $fillable = ['listing_id', 'brand', 'gender', 'genders', 'material', 'sizes', 'colors'];
    protected function casts(): array { return ['genders' => 'array', 'sizes' => 'array', 'colors' => 'array']; }
    public function listing(): BelongsTo { return $this->belongsTo(MarketplaceListing::class, 'listing_id'); }
}
