<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MarketplaceListingVariant extends Model {
    protected $fillable=['listing_id','sku','options','option_key','price','stock_quantity','is_active'];
    protected function casts():array{return ['options'=>'array','price'=>'decimal:2','stock_quantity'=>'integer','is_active'=>'boolean'];}
    public function listing():BelongsTo{return $this->belongsTo(MarketplaceListing::class,'listing_id');}
    public static function optionKey(array $options):string{ksort($options);return collect($options)->map(fn($value,$key)=>strtolower("{$key}:{$value}"))->implode('|');}
}
