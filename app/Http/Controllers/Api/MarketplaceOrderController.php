<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMarketplaceOrderRequest;
use App\Http\Resources\MarketplaceOrderResource;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceListingVariant;
use App\Models\MarketplaceDeliveryZone;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Jobs\SendMarketplaceOrderNotification;
class MarketplaceOrderController extends Controller {
    public function store(StoreMarketplaceOrderRequest $request) {
        $validated=$request->validated(); $user=$request->user('sanctum');
        if(!empty($validated['checkout_token'])&&($existing=MarketplaceOrder::where('checkout_token',$validated['checkout_token'])->first()))return new MarketplaceOrderResource($existing->load(['items','deliveryZone']));
        $checkoutToken=$validated['checkout_token']??(string)Str::uuid();
        $order=DB::transaction(function() use($validated,$user,$checkoutToken) {
            $lines=[]; $subtotal=0;
            foreach($validated['items'] as $input) {
                $listing=MarketplaceListing::query()->lockForUpdate()->findOrFail($input['listing_id']);
                if($listing->status!=='active') throw ValidationException::withMessages(['items'=>["{$listing->name} is not available."]]);
                if($listing->stock_quantity!==null && $input['quantity']>$listing->stock_quantity) throw ValidationException::withMessages(['items'=>["Only {$listing->stock_quantity} of {$listing->name} are available."]]);
                $unit=$input['purchase_type']==='deposit' ? $listing->deposit_amount : $listing->price;
                if($unit===null) throw ValidationException::withMessages(['items'=>["{$listing->name} does not support deposits."]]);
                if($input['purchase_type']==='deposit' && $input['quantity']!==1) throw ValidationException::withMessages(['items'=>['Deposit reservations must have a quantity of one.']]);
                $listing->loadMissing(['category', 'fashionDetails']);
                $variant = isset($input['variant_id']) ? MarketplaceListingVariant::query()->lockForUpdate()->where('listing_id',$listing->id)->find($input['variant_id']) : null;
                if ($listing->variants()->where('is_active', true)->exists()) {
                    if (!$variant || !$variant->is_active || MarketplaceListingVariant::optionKey($variant->options) !== MarketplaceListingVariant::optionKey($input['selected_options'] ?? [])) {
                        throw ValidationException::withMessages(['items' => ["Select an available option combination for {$listing->name}."]]);
                    }
                    if ($input['quantity'] > $variant->stock_quantity) throw ValidationException::withMessages(['items' => ["Only {$variant->stock_quantity} of this {$listing->name} option are available."]]);
                }
                if ($listing->category?->slug === 'fashion') {
                    $options = $input['selected_options'] ?? [];
                    $sizes = $listing->fashionDetails?->sizes ?? [];
                    $colors = $listing->fashionDetails?->colors ?? [];
                    $genders = $listing->fashionDetails?->genders ?? ($listing->fashionDetails?->gender ? [$listing->fashionDetails->gender] : []);
                    if ($genders && (!isset($options['gender']) || !in_array($options['gender'], $genders, true))) {
                        throw ValidationException::withMessages(['items' => ["Select an available gender for {$listing->name}."]]);
                    }
                    if ($sizes && (!isset($options['size']) || !in_array($options['size'], $sizes, true))) {
                        throw ValidationException::withMessages(['items' => ["Select an available size for {$listing->name}."]]);
                    }
                    if ($colors && (!isset($options['color']) || !in_array($options['color'], $colors, true))) {
                        throw ValidationException::withMessages(['items' => ["Select an available color for {$listing->name}."]]);
                    }
                }
                if ($input['purchase_type']==='full' && $variant?->price !== null) $unit=$variant->price;
                if($input['purchase_type']==='deposit')$listing->update(['status'=>'reserved']);
                elseif($variant){$variant->decrement('stock_quantity',$input['quantity']);if(!$listing->variants()->where('is_active',true)->where('stock_quantity','>',0)->exists())$listing->update(['status'=>'out_of_stock']);}
                elseif($listing->stock_quantity!==null){$listing->decrement('stock_quantity',$input['quantity']);if($listing->fresh()->stock_quantity===0)$listing->update(['status'=>'out_of_stock']);}
                $line=(float)$unit*$input['quantity']; $subtotal+=$line;
                $lines[]=['listing_id'=>$listing->id,'variant_id'=>$variant?->id,'listing_name'=>$listing->name,'listing_sku'=>$variant?->sku??$listing->sku,'quantity'=>$input['quantity'],'purchase_type'=>$input['purchase_type'],'unit_price'=>$unit,'line_total'=>$line,'selected_options'=>$input['selected_options']??null,'stock_reserved_at'=>now()];
            }
            $zone=$validated['fulfillment_type']==='delivery'?MarketplaceDeliveryZone::where('is_active',true)->findOrFail($validated['delivery_zone_id']):null;
            $deliveryFee=(float)($zone?->fee??0); $total=$subtotal+$deliveryFee;
            $order=MarketplaceOrder::create(['player_id'=>$user?->role===User::ROLE_PLAYER?$user->id:null,'checkout_token'=>$checkoutToken,'reference_code'=>'FFM-'.strtoupper(Str::random(8)),
                'name'=>$validated['name'],'phone'=>$validated['phone'],'whatsapp_opt_in'=>$validated['whatsapp_opt_in']??false,'email'=>$validated['email'],'fulfillment_type'=>$validated['fulfillment_type'],
                'delivery_zone_id'=>$zone?->id,'delivery_address'=>$validated['delivery_address']??null,'notes'=>$validated['notes']??null,'subtotal'=>$subtotal,'delivery_fee'=>$deliveryFee,'total'=>$total]);
            $order->items()->createMany($lines); return $order;
        });
        SendMarketplaceOrderNotification::dispatch($order->id,'order_received')->afterCommit();
        return (new MarketplaceOrderResource($order->load(['items','deliveryZone'])))->response()->setStatusCode(201);
    }
}
