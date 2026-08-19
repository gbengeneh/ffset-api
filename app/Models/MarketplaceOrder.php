<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class MarketplaceOrder extends Model {
    protected $fillable = ['player_id','checkout_token','reference_code','name','phone','whatsapp_opt_in','email','fulfillment_type','delivery_zone_id','delivery_address','notes','subtotal','delivery_fee','total','status','payment_status','payment_reference','tracking_reference','internal_notes','cancellation_reason','processing_at','dispatched_at','delivered_at','cancelled_at'];
    protected function casts(): array { return ['whatsapp_opt_in'=>'boolean','subtotal'=>'decimal:2','delivery_fee'=>'decimal:2','total'=>'decimal:2','processing_at'=>'datetime','dispatched_at'=>'datetime','delivered_at'=>'datetime','cancelled_at'=>'datetime']; }
    public function player(): BelongsTo { return $this->belongsTo(User::class, 'player_id'); }
    public function items(): HasMany { return $this->hasMany(MarketplaceOrderItem::class, 'order_id'); }
    public function deliveryZone(): BelongsTo { return $this->belongsTo(MarketplaceDeliveryZone::class,'delivery_zone_id'); }
    public function paymentAttempts(): HasMany { return $this->hasMany(MarketplacePaymentAttempt::class,'order_id'); }
}
