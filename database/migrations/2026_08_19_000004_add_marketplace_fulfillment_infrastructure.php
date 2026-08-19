<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void{
        Schema::create('marketplace_delivery_zones',function(Blueprint $table){$table->id();$table->string('name');$table->string('state')->nullable();$table->json('cities')->nullable();$table->decimal('fee',14,2);$table->string('estimated_delivery')->nullable();$table->boolean('is_active')->default(true);$table->timestamps();});
        Schema::table('marketplace_orders',function(Blueprint $table){$table->foreignId('delivery_zone_id')->nullable()->after('fulfillment_type')->constrained('marketplace_delivery_zones')->nullOnDelete();$table->decimal('delivery_fee',14,2)->default(0)->after('subtotal');$table->string('tracking_reference')->nullable();$table->text('internal_notes')->nullable();$table->text('cancellation_reason')->nullable();$table->timestamp('processing_at')->nullable();$table->timestamp('dispatched_at')->nullable();$table->timestamp('delivered_at')->nullable();$table->timestamp('cancelled_at')->nullable();});
        Schema::create('marketplace_payment_attempts',function(Blueprint $table){$table->id();$table->foreignId('order_id')->constrained('marketplace_orders')->cascadeOnDelete();$table->string('provider')->default('paystack');$table->string('reference')->unique();$table->decimal('amount',14,2);$table->string('status')->default('initialized');$table->json('provider_response')->nullable();$table->timestamp('paid_at')->nullable();$table->timestamps();});
        Schema::create('marketplace_notification_logs',function(Blueprint $table){$table->id();$table->foreignId('order_id')->constrained('marketplace_orders')->cascadeOnDelete();$table->string('channel');$table->string('event');$table->string('recipient');$table->string('status');$table->text('error')->nullable();$table->timestamp('sent_at')->nullable();$table->timestamps();$table->unique(['order_id','channel','event']);});
    }
    public function down():void{Schema::dropIfExists('marketplace_notification_logs');Schema::dropIfExists('marketplace_payment_attempts');Schema::table('marketplace_orders',function(Blueprint $table){$table->dropConstrainedForeignId('delivery_zone_id');$table->dropColumn(['delivery_fee','tracking_reference','internal_notes','cancellation_reason','processing_at','dispatched_at','delivered_at','cancelled_at']);});Schema::dropIfExists('marketplace_delivery_zones');}
};
