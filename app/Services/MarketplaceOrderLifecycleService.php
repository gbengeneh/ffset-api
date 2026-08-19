<?php
namespace App\Services;
use App\Models\MarketplaceOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class MarketplaceOrderLifecycleService {
    public function transition(MarketplaceOrder $order,string $status,array $data=[]):MarketplaceOrder{
        $allowed=['pending'=>['confirmed','cancelled'],'confirmed'=>['processing'],'processing'=>['ready_for_pickup','dispatched'],'ready_for_pickup'=>['delivered'],'dispatched'=>['delivered'],'delivered'=>[],'cancelled'=>[]];
        if($status===$order->status)return $order;
        if(!in_array($status,$allowed[$order->status]??[],true))throw ValidationException::withMessages(['status'=>["Cannot move an order from {$order->status} to {$status}."]]);
        if($status==='confirmed'&&$order->payment_status!=='paid')throw ValidationException::withMessages(['status'=>['Payment must be confirmed first.']]);
        if($status==='dispatched'&&empty($data['tracking_reference']))throw ValidationException::withMessages(['tracking_reference'=>['A tracking reference is required before dispatch.']]);
        if($status==='cancelled'&&$order->payment_status==='paid')throw ValidationException::withMessages(['status'=>['Refund this paid order instead of cancelling it directly.']]);
        if($status==='cancelled')$this->restock($order);
        $timestamps=match($status){'processing'=>['processing_at'=>now()],'dispatched'=>['dispatched_at'=>now()],'delivered'=>['delivered_at'=>now()],'cancelled'=>['cancelled_at'=>now()],default=>[]};
        $order->update($timestamps+['status'=>$status,'tracking_reference'=>$data['tracking_reference']??$order->tracking_reference,'internal_notes'=>$data['internal_notes']??$order->internal_notes,'cancellation_reason'=>$data['cancellation_reason']??$order->cancellation_reason]);
        \App\Jobs\SendMarketplaceOrderNotification::dispatch($order->id,$status)->afterCommit();return $order->fresh(['items','deliveryZone','paymentAttempts']);
    }
    public function restock(MarketplaceOrder $order):void{DB::transaction(function()use($order){$order->load(['items.listing','items.variant']);foreach($order->items as $item){$item=$item->newQuery()->lockForUpdate()->find($item->id);if($item->stock_released_at||(!$item->stock_reserved_at&&$order->payment_status==='unpaid'))continue;if($item->purchase_type==='deposit'){if($item->listing?->status==='reserved')$item->listing->update(['status'=>'active']);}elseif($item->variant){$item->variant->increment('stock_quantity',$item->quantity);$item->listing?->update(['status'=>'active']);}elseif($item->listing?->stock_quantity!==null){$item->listing->increment('stock_quantity',$item->quantity);$item->listing->update(['status'=>'active']);}$item->update(['stock_released_at'=>now()]);}});}
    public function finalizeRefund(MarketplaceOrder $order,?string $reason=null):MarketplaceOrder{if($order->payment_status==='refunded')return $order;$this->restock($order);$order->update(['payment_status'=>'refunded','status'=>'cancelled','cancellation_reason'=>$reason??$order->cancellation_reason,'cancelled_at'=>now()]);\App\Jobs\SendMarketplaceOrderNotification::dispatch($order->id,'refunded')->afterCommit();return $order->fresh(['items','deliveryZone','paymentAttempts']);}
}
