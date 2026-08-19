<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MarketplacePaymentAttempt extends Model {protected $fillable=['order_id','provider','reference','amount','status','provider_response','paid_at'];protected function casts():array{return ['amount'=>'decimal:2','provider_response'=>'array','paid_at'=>'datetime'];}public function order():BelongsTo{return $this->belongsTo(MarketplaceOrder::class,'order_id');}}
