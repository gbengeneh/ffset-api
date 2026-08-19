<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceOrderResource;
use App\Models\MarketplaceOrder;
use Illuminate\Http\Request;
use App\Services\MarketplaceOrderPaymentService;
use App\Services\MarketplaceOrderLifecycleService;
use App\Services\PaystackService;
class MarketplaceOrderController extends Controller {
    public function __construct(private MarketplaceOrderPaymentService $payments,private MarketplaceOrderLifecycleService $lifecycle,private PaystackService $paystack) {}
    public function index() { return MarketplaceOrderResource::collection(MarketplaceOrder::with(['items','deliveryZone','paymentAttempts'])->latest()->paginate(50)); }
    public function show(MarketplaceOrder $order){return new MarketplaceOrderResource($order->load(['items','deliveryZone','paymentAttempts']));}
    public function update(Request $request, MarketplaceOrder $order) {
        $validated=$request->validate(['status'=>['sometimes','in:pending,confirmed,processing,ready_for_pickup,dispatched,delivered,cancelled'],'payment_status'=>['sometimes','in:unpaid,paid'],'tracking_reference'=>['nullable','string','max:150'],'internal_notes'=>['nullable','string','max:5000'],'cancellation_reason'=>['nullable','string','max:2000']]);
        if(($validated['payment_status']??null)==='paid' && $order->payment_status!=='paid') {
            $order=$this->payments->confirm($order,['status'=>'success','amount'=>(int)round((float)$order->total*100)]);
            unset($validated['payment_status']);
        }
        if(isset($validated['status'])){$status=$validated['status'];unset($validated['status']);$order=$this->lifecycle->transition($order,$status,$validated);}elseif($validated)$order->update($validated);
        return new MarketplaceOrderResource($order->load('items'));
    }
    public function refund(Request $request,MarketplaceOrder $order){$validated=$request->validate(['reason'=>['required','string','max:2000']]);abort_unless($order->payment_status==='paid'&&$order->payment_reference,422,'Only paid online orders can be refunded.');$response=$this->paystack->refund($order->payment_reference,(int)round((float)$order->total*100));$order->update(['payment_status'=>'refund_pending','cancellation_reason'=>$validated['reason']]);if(($response['status']??null)==='processed')$order=$this->lifecycle->finalizeRefund($order,$validated['reason']);return new MarketplaceOrderResource($order->fresh()->load(['items','deliveryZone','paymentAttempts']));}
}
