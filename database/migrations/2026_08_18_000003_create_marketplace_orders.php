<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id(); $table->foreignId('player_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_code')->unique(); $table->string('name'); $table->string('phone'); $table->string('email');
            $table->string('fulfillment_type')->default('pickup'); $table->text('delivery_address')->nullable(); $table->text('notes')->nullable();
            $table->decimal('subtotal', 14, 2); $table->decimal('total', 14, 2); $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid'); $table->timestamps();
        });
        Schema::create('marketplace_order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained('marketplace_orders')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('marketplace_listings')->nullOnDelete();
            $table->string('listing_name'); $table->string('listing_sku')->nullable(); $table->unsignedInteger('quantity');
            $table->string('purchase_type')->default('full'); $table->decimal('unit_price', 14, 2); $table->decimal('line_total', 14, 2);
            $table->json('selected_options')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('marketplace_order_items'); Schema::dropIfExists('marketplace_orders'); }
};
