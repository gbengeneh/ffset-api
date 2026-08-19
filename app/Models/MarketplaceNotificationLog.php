<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MarketplaceNotificationLog extends Model {protected $fillable=['order_id','channel','event','recipient','status','error','sent_at'];protected function casts():array{return ['sent_at'=>'datetime'];}}
