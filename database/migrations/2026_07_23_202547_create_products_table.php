<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['wine', 'drink', 'gaming_package', 'service']);
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('size')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('image_url')->nullable();
            $table->boolean('is_stocked')->default(true);
            $table->integer('stock_quantity')->nullable();
            $table->integer('low_stock_threshold')->default(5);
            $table->enum('status', ['active', 'inactive', 'reserve_only'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
