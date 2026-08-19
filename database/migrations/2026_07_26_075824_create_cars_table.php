<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->decimal('price', 14, 2);
            $table->decimal('deposit_amount', 14, 2);
            $table->unsignedInteger('mileage')->nullable();
            $table->enum('condition', ['new', 'used', 'certified_pre_owned'])->default('used');
            $table->enum('transmission', ['automatic', 'manual'])->default('automatic');
            $table->enum('fuel_type', ['petrol', 'diesel', 'hybrid', 'electric'])->default('petrol');
            $table->string('color')->nullable();
            $table->string('vin')->nullable()->unique();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->foreignId('deposit_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
