<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('entry_fee_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('entry_fee', 12, 2)->default(0);
            $table->decimal('first_prize', 12, 2)->default(0);
            $table->decimal('second_prize', 12, 2)->default(0);
            $table->decimal('third_prize', 12, 2)->default(0);
            $table->json('rules')->nullable();
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();
            $table->timestamp('event_date')->nullable();
            $table->enum('status', ['upcoming', 'open', 'closed', 'completed'])->default('upcoming');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
