<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreMarketplaceOrderRequest extends FormRequest {
 public function authorize():bool{return true;}
 public function rules():array{return [
  'checkout_token'=>['nullable','uuid'],'name'=>['required','string','max:150'],'phone'=>['required','string','max:30'],'whatsapp_opt_in'=>['nullable','boolean'],'email'=>['required','email','max:255'],
  'fulfillment_type'=>['required','in:pickup,delivery'],'delivery_address'=>['required_if:fulfillment_type,delivery','nullable','string','max:2000'],'delivery_zone_id'=>['required_if:fulfillment_type,delivery','nullable','exists:marketplace_delivery_zones,id'],
  'notes'=>['nullable','string','max:2000'],'items'=>['required','array','min:1','max:30'],'items.*.listing_id'=>['required','integer','exists:marketplace_listings,id'],'items.*.quantity'=>['required','integer','min:1','max:20'],
  'items.*.variant_id'=>['nullable','integer','exists:marketplace_listing_variants,id'],'items.*.purchase_type'=>['required','in:full,deposit'],'items.*.selected_options'=>['nullable','array'],
 ];}
}
