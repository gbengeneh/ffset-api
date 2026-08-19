<?php
namespace App\Services;
use App\Models\MarketplaceOrder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use App\Models\MarketplacePaymentAttempt;
class MarketplaceOrderPaymentService {
    public function confirm(MarketplaceOrder $order, array $paystackData): MarketplaceOrder {
        $wasPaid=$order->payment_status==='paid';
        $result=DB::transaction(function() use($order,$paystackData) {
            $order=MarketplaceOrder::query()->lockForUpdate()->with(['items.listing','items.variant'])->findOrFail($order->id);
            if($order->payment_status==='paid') return $order;
            if(($paystackData['status']??null)!=='success') throw new RuntimeException('Payment has not been completed.');
            if((int)($paystackData['amount']??-1)!==(int)round((float)$order->total*100)) throw new RuntimeException('Payment amount does not match this order.');
            foreach($order->items as $item) {
                $listing=$item->listing;
                if(!$listing) continue;
                if($item->stock_reserved_at) continue;
                if($item->purchase_type==='deposit') { $listing->update(['status'=>'reserved']); continue; }
                if($item->variant) {
                    $variant=$item->variant()->lockForUpdate()->first();
                    if($variant->stock_quantity<$item->quantity) throw new RuntimeException("Insufficient stock for {$item->listing_name}.");
                    $variant->update(['stock_quantity'=>$variant->stock_quantity-$item->quantity]);
                    if(!$listing->variants()->where('is_active',true)->where('stock_quantity','>',0)->exists())$listing->update(['status'=>'out_of_stock']);
                    continue;
                }
                if($listing->stock_quantity!==null) {
                    if($listing->stock_quantity<$item->quantity) throw new RuntimeException("Insufficient stock for {$item->listing_name}.");
                    $remaining=$listing->stock_quantity-$item->quantity;
                    $listing->update(['stock_quantity'=>$remaining,'status'=>$remaining===0?'out_of_stock':$listing->status]);
                }
            }
            $order->update(['payment_status'=>'paid','status'=>'confirmed']);
            if(!empty($paystackData['reference']))MarketplacePaymentAttempt::where('reference',$paystackData['reference'])->update(['status'=>'paid','provider_response'=>$paystackData,'paid_at'=>now()]);
            return $order->fresh('items');
        });
        if(!$wasPaid&&$result->payment_status==='paid')\App\Jobs\SendMarketplaceOrderNotification::dispatch($result->id,'payment_confirmed')->afterCommit();
        return $result;
    }
}
