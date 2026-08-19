<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MarketplaceDeliveryZone extends Model {protected $fillable=['name','state','cities','fee','estimated_delivery','is_active'];protected function casts():array{return ['cities'=>'array','fee'=>'decimal:2','is_active'=>'boolean'];}public function orders(){return $this->hasMany(MarketplaceOrder::class,'delivery_zone_id');}}
