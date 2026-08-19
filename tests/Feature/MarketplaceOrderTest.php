<?php
namespace Tests\Feature;
use App\Models\MarketplaceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceListingVariant;
use App\Models\MarketplaceDeliveryZone;
use App\Services\MarketplaceOrderPaymentService;
use App\Services\MarketplaceNotificationService;
use App\Services\MarketplaceOrderLifecycleService;
class MarketplaceOrderTest extends TestCase {
    use RefreshDatabase;
    public function test_order_prices_are_calculated_from_the_database(): void {
        $category=MarketplaceCategory::firstOrCreate(['slug'=>'gadgets'],['name'=>'Gadgets']);
        $listing=$category->listings()->create(['name'=>'Phone','slug'=>'phone','price'=>500000,'condition'=>'new','status'=>'active','stock_quantity'=>3]);
        $this->postJson('/api/marketplace/orders',['name'=>'Ada','phone'=>'08000000000','email'=>'ada@example.com','fulfillment_type'=>'pickup','items'=>[['listing_id'=>$listing->id,'quantity'=>2,'purchase_type'=>'full','price'=>1]]])
            ->assertCreated()->assertJsonPath('total','1000000.00')->assertJsonPath('items.0.unit_price','500000.00');
    }
    public function test_deposit_requires_listing_support_and_quantity_one(): void {
        $category=MarketplaceCategory::where('slug','autos')->firstOrFail();
        $listing=$category->listings()->create(['name'=>'Car','slug'=>'car','price'=>5000000,'deposit_amount'=>500000,'condition'=>'used','status'=>'active']);
        $base=['name'=>'Ada','phone'=>'08000000000','email'=>'ada@example.com','fulfillment_type'=>'pickup'];
        $this->postJson('/api/marketplace/orders',$base+['items'=>[['listing_id'=>$listing->id,'quantity'=>2,'purchase_type'=>'deposit']]])->assertUnprocessable();
        $this->postJson('/api/marketplace/orders',$base+['items'=>[['listing_id'=>$listing->id,'quantity'=>1,'purchase_type'=>'deposit']]])->assertCreated()->assertJsonPath('total','500000.00');
    }
    public function test_paystack_verification_is_idempotent_and_reduces_stock_once(): void {
        config(['services.paystack.secret_key'=>'sk_test_fake']);
        $category=MarketplaceCategory::firstOrCreate(['slug'=>'gadgets'],['name'=>'Gadgets']);
        $listing=$category->listings()->create(['name'=>'Phone','slug'=>'pay-phone','price'=>500000,'condition'=>'new','status'=>'active','stock_quantity'=>3]);
        $orderReference=$this->postJson('/api/marketplace/orders',['name'=>'Ada','phone'=>'08000000000','email'=>'ada@example.com','fulfillment_type'=>'pickup','items'=>[['listing_id'=>$listing->id,'quantity'=>2,'purchase_type'=>'full']]])->json('reference_code');
        Http::fake(['api.paystack.co/transaction/initialize'=>Http::response(['status'=>true,'data'=>['authorization_url'=>'https://checkout.paystack.com/test','access_code'=>'test','reference'=>'test']])]);
        $this->postJson("/api/payments/marketplace-orders/{$orderReference}/initialize")->assertOk();
        $reference=MarketplaceOrder::where('reference_code',$orderReference)->value('payment_reference');
        Http::fake(['api.paystack.co/transaction/verify/*'=>Http::response(['status'=>true,'data'=>['status'=>'success','reference'=>$reference,'amount'=>100000000]])]);
        $this->getJson("/api/payments/marketplace/verify/{$reference}")->assertOk()->assertJsonPath('payment_status','paid');
        $this->getJson("/api/payments/marketplace/verify/{$reference}")->assertOk();
        $this->assertSame(1,$listing->fresh()->stock_quantity);
    }
    public function test_fashion_orders_require_an_available_size_and_color(): void {
        $category=MarketplaceCategory::firstOrCreate(['slug'=>'fashion'],['name'=>'Fashion']);
        $listing=$category->listings()->create(['name'=>'Jacket','slug'=>'jacket','price'=>85000,'condition'=>'new','status'=>'active','stock_quantity'=>5]);
        $listing->fashionDetails()->create(['genders'=>['men','women'],'sizes'=>['M','L'],'colors'=>['Black','Olive']]);
        $base=['name'=>'Ada','phone'=>'08000000000','email'=>'ada@example.com','fulfillment_type'=>'pickup'];
        $this->postJson('/api/marketplace/orders',$base+['items'=>[['listing_id'=>$listing->id,'quantity'=>1,'purchase_type'=>'full']]])->assertUnprocessable();
        $this->postJson('/api/marketplace/orders',$base+['items'=>[['listing_id'=>$listing->id,'quantity'=>1,'purchase_type'=>'full','selected_options'=>['gender'=>'men','size'=>'XL','color'=>'Black']]]])->assertUnprocessable();
        $this->postJson('/api/marketplace/orders',$base+['items'=>[['listing_id'=>$listing->id,'quantity'=>1,'purchase_type'=>'full','selected_options'=>['gender'=>'women','size'=>'M','color'=>'Black']]]])->assertCreated()->assertJsonPath('items.0.selected_options.gender','women');
    }
    public function test_variant_stock_and_delivery_fee_are_enforced_by_the_server(): void {
        $category=MarketplaceCategory::firstOrCreate(['slug'=>'fashion'],['name'=>'Fashion']);
        $listing=$category->listings()->create(['name'=>'Variant Jacket','slug'=>'variant-jacket','price'=>85000,'condition'=>'new','status'=>'active']);
        $listing->fashionDetails()->create(['genders'=>['women'],'sizes'=>['M'],'colors'=>['Black']]);
        $options=['gender'=>'women','size'=>'M','color'=>'Black'];
        $variant=$listing->variants()->create(['options'=>$options,'option_key'=>MarketplaceListingVariant::optionKey($options),'sku'=>'VJ-W-M-BLK','price'=>90000,'stock_quantity'=>2,'is_active'=>true]);
        $zone=MarketplaceDeliveryZone::create(['name'=>'Test Delivery','fee'=>5000,'is_active'=>true]);
        $response=$this->postJson('/api/marketplace/orders',['name'=>'Ada','phone'=>'08000000000','email'=>'ada@example.com','fulfillment_type'=>'delivery','delivery_zone_id'=>$zone->id,'delivery_address'=>'12 Test Street','items'=>[['listing_id'=>$listing->id,'variant_id'=>$variant->id,'quantity'=>2,'purchase_type'=>'full','selected_options'=>$options]]]);
        $response->assertCreated()->assertJsonPath('subtotal','180000.00')->assertJsonPath('delivery_fee','5000.00')->assertJsonPath('total','185000.00');
        $order=MarketplaceOrder::findOrFail($response->json('id'));
        app(MarketplaceOrderPaymentService::class)->confirm($order,['status'=>'success','amount'=>18500000,'reference'=>'VARIANT-PAY']);
        $this->assertSame(0,$variant->fresh()->stock_quantity);$this->assertSame('out_of_stock',$listing->fresh()->status);
    }

    public function test_variant_options_must_match_the_selected_variant(): void {
        $category=MarketplaceCategory::firstOrCreate(['slug'=>'fashion'],['name'=>'Fashion']);
        $listing=$category->listings()->create(['name'=>'Option Jacket','slug'=>'option-jacket','price'=>85000,'condition'=>'new','status'=>'active']);
        $listing->fashionDetails()->create(['genders'=>['women'],'sizes'=>['M'],'colors'=>['Black']]);
        $options=['gender'=>'women','size'=>'M','color'=>'Black'];$variant=$listing->variants()->create(['options'=>$options,'option_key'=>MarketplaceListingVariant::optionKey($options),'stock_quantity'=>2,'is_active'=>true]);
        $this->postJson('/api/marketplace/orders',['name'=>'Ada','phone'=>'08000000000','email'=>'ada@example.com','fulfillment_type'=>'pickup','items'=>[['listing_id'=>$listing->id,'variant_id'=>$variant->id,'quantity'=>1,'purchase_type'=>'full','selected_options'=>['gender'=>'women','size'=>'M','color'=>'Olive']]]])->assertUnprocessable();
    }

    public function test_stock_is_reserved_at_checkout_and_released_only_once_when_cancelled(): void {
        $category=MarketplaceCategory::firstOrCreate(['slug'=>'gadgets'],['name'=>'Gadgets']);
        $listing=$category->listings()->create(['name'=>'Tablet','slug'=>'reservation-tablet','price'=>250000,'condition'=>'new','status'=>'active','stock_quantity'=>2]);
        $response=$this->postJson('/api/marketplace/orders',['name'=>'Ada','phone'=>'08000000000','email'=>'ada@example.com','fulfillment_type'=>'pickup','items'=>[['listing_id'=>$listing->id,'quantity'=>2,'purchase_type'=>'full']]])->assertCreated();
        $order=MarketplaceOrder::findOrFail($response->json('id'));

        $this->assertSame(0,$listing->fresh()->stock_quantity);
        $this->assertNotNull($order->items()->firstOrFail()->stock_reserved_at);

        app(MarketplaceOrderLifecycleService::class)->transition($order,'cancelled',['cancellation_reason'=>'Payment window expired.']);
        $this->assertSame(2,$listing->fresh()->stock_quantity);

        app(MarketplaceOrderLifecycleService::class)->restock($order->fresh());
        $this->assertSame(2,$listing->fresh()->stock_quantity);
    }

    public function test_whatsapp_requires_opt_in_and_uses_an_approved_template(): void {
        Mail::fake();
        Http::fake(['graph.facebook.com/*'=>Http::response(['messages'=>[['id'=>'wamid.test']]],200)]);
        config([
            'services.whatsapp.token'=>'test-token',
            'services.whatsapp.phone_number_id'=>'12345',
            'services.whatsapp.templates.order_received'=>'ffset_order_received',
        ]);
        $category=MarketplaceCategory::firstOrCreate(['slug'=>'gadgets'],['name'=>'Gadgets']);
        $listing=$category->listings()->create(['name'=>'Watch','slug'=>'notification-watch','price'=>100000,'condition'=>'new','status'=>'active','stock_quantity'=>2]);
        $order=MarketplaceOrder::create(['reference_code'=>'FFM-NOTIFY','name'=>'Ada','phone'=>'08012345678','email'=>'ada@example.com','whatsapp_opt_in'=>true,'fulfillment_type'=>'pickup','subtotal'=>100000,'delivery_fee'=>0,'total'=>100000]);
        $order->items()->create(['listing_id'=>$listing->id,'listing_name'=>$listing->name,'quantity'=>1,'purchase_type'=>'full','unit_price'=>100000,'line_total'=>100000]);

        app(MarketplaceNotificationService::class)->send($order,'order_received');

        Http::assertSent(fn($request)=>$request->url()==='https://graph.facebook.com/v23.0/12345/messages'
            && $request['to']==='2348012345678'
            && $request['type']==='template'
            && $request['template']['name']==='ffset_order_received');
        $this->assertDatabaseHas('marketplace_notification_logs',['order_id'=>$order->id,'channel'=>'whatsapp','event'=>'order_received','status'=>'sent']);

        Http::fake();
        $order->update(['whatsapp_opt_in'=>false]);
        app(MarketplaceNotificationService::class)->send($order,'payment_confirmed');
        Http::assertNothingSent();
    }
}
