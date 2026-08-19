<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class MarketplaceOrderResource extends JsonResource {
    public function toArray(Request $request): array { return [
        'id'=>$this->id,'reference_code'=>$this->reference_code,'name'=>$this->name,'phone'=>$this->phone,'whatsapp_opt_in'=>$this->whatsapp_opt_in,'email'=>$this->email,
        'fulfillment_type'=>$this->fulfillment_type,'delivery_address'=>$this->delivery_address,'notes'=>$this->notes,
        'subtotal'=>$this->subtotal,'delivery_fee'=>$this->delivery_fee,'total'=>$this->total,'status'=>$this->status,'payment_status'=>$this->payment_status,'created_at'=>$this->created_at,
        'delivery_zone'=>$this->whenLoaded('deliveryZone',fn()=> $this->deliveryZone ? ['id'=>$this->deliveryZone->id,'name'=>$this->deliveryZone->name,'estimated_delivery'=>$this->deliveryZone->estimated_delivery] : null),
        'tracking_reference'=>$this->tracking_reference,
        'internal_notes'=>$this->when($request->user()?->role === \App\Models\User::ROLE_ADMIN, $this->internal_notes),
        'cancellation_reason'=>$this->cancellation_reason,
        'payment_attempts'=>$this->whenLoaded('paymentAttempts',fn()=> $this->paymentAttempts->map(fn($attempt)=>['id'=>$attempt->id,'reference'=>$attempt->reference,'provider'=>$attempt->provider,'amount'=>$attempt->amount,'status'=>$attempt->status,'paid_at'=>$attempt->paid_at,'created_at'=>$attempt->created_at])),
        'items'=>$this->whenLoaded('items', fn()=> $this->items->map(fn($item)=>[
            'id'=>$item->id,'listing_id'=>$item->listing_id,'variant_id'=>$item->variant_id,'listing_name'=>$item->listing_name,'listing_sku'=>$item->listing_sku,
            'quantity'=>$item->quantity,'purchase_type'=>$item->purchase_type,'unit_price'=>$item->unit_price,'line_total'=>$item->line_total,
            'selected_options'=>$item->selected_options ?? [],
        ])),
    ]; }
}
