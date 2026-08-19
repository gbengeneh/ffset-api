<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MarketplaceOrderItem extends Model {
    protected $fillable = ['order_id','listing_id','variant_id','listing_name','listing_sku','quantity','purchase_type','unit_price','line_total','selected_options','stock_reserved_at','stock_released_at'];
    protected function casts(): array { return ['quantity'=>'integer','unit_price'=>'decimal:2','line_total'=>'decimal:2','selected_options'=>'array','stock_reserved_at'=>'datetime','stock_released_at'=>'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(MarketplaceOrder::class, 'order_id'); }
    public function listing(): BelongsTo { return $this->belongsTo(MarketplaceListing::class, 'listing_id'); }
    public function variant(): BelongsTo { return $this->belongsTo(MarketplaceListingVariant::class, 'variant_id'); }
}
