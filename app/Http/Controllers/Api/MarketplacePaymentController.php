<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceOrderResource;
use App\Models\MarketplaceOrder;
use App\Services\MarketplaceOrderPaymentService;
use App\Services\PaystackService;
use Illuminate\Support\Str;
use App\Models\MarketplacePaymentAttempt;
class MarketplacePaymentController extends Controller {
    public function __construct(private PaystackService $paystack,private MarketplaceOrderPaymentService $payments) {}
    public function initialize(MarketplaceOrder $order) {
        if($order->payment_status!=='unpaid'||in_array($order->status,['cancelled','completed'],true)) return response()->json(['message'=>'This order is not awaiting payment.'],422);
        if(!$order->payment_reference)$order->update(['payment_reference'=>'FFM-PAY-'.strtoupper(Str::random(12))]);
        $result=$this->paystack->initialize(['email'=>$order->email,'amount'=>(int)round((float)$order->total*100),'reference'=>$order->payment_reference,
            'callback_url'=>rtrim(config('app.frontend_autos_url'),'/').'/payment/marketplace-callback','metadata'=>['marketplace_order_id'=>$order->id,'order_reference'=>$order->reference_code]]);
        MarketplacePaymentAttempt::updateOrCreate(['reference'=>$order->payment_reference],['order_id'=>$order->id,'provider'=>'paystack','amount'=>$order->total,'status'=>'initialized','provider_response'=>$result]);
        return response()->json($result);
    }
    public function verify(string $reference) {
        $order=MarketplaceOrder::where('payment_reference',$reference)->firstOrFail();
        $data=$this->paystack->verify($reference);
        if(($data['status']??null)==='success')$order=$this->payments->confirm($order,$data);
        return new MarketplaceOrderResource($order->fresh('items'));
    }
}
