<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('marketplace_listing_variants', function(Blueprint $table){
            $table->id(); $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
            $table->string('sku')->nullable()->unique(); $table->json('options'); $table->string('option_key');
            $table->decimal('price',14,2)->nullable(); $table->unsignedInteger('stock_quantity')->default(0); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->unique(['listing_id','option_key']);
        });
        Schema::table('marketplace_order_items', fn(Blueprint $table)=>$table->foreignId('variant_id')->nullable()->after('listing_id')->constrained('marketplace_listing_variants')->nullOnDelete());
    }
    public function down(): void {
        Schema::table('marketplace_order_items', fn(Blueprint $table)=>$table->dropConstrainedForeignId('variant_id'));
        Schema::dropIfExists('marketplace_listing_variants');
    }
};
