<?php
namespace Database\Seeders;
use App\Models\MarketplaceDeliveryZone;
use Illuminate\Database\Seeder;
class MarketplaceDeliveryZoneSeeder extends Seeder {public function run():void{foreach([['name'=>'Akure Pickup','state'=>'Ondo','cities'=>['Akure'],'fee'=>0,'estimated_delivery'=>'Same day'],['name'=>'Ondo State Delivery','state'=>'Ondo','cities'=>[],'fee'=>5000,'estimated_delivery'=>'1–2 business days'],['name'=>'Nationwide Delivery','state'=>null,'cities'=>[],'fee'=>12000,'estimated_delivery'=>'3–7 business days']] as $zone)MarketplaceDeliveryZone::updateOrCreate(['name'=>$zone['name']],$zone+['is_active'=>true]);}}
