<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('delivery_address')->nullable();
            $table->foreignId('preferred_delivery_zone_id')->nullable()->constrained('marketplace_delivery_zones')->nullOnDelete();
            $table->string('preferred_fulfillment_type')->default('pickup');
            $table->boolean('whatsapp_opt_in')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preferred_delivery_zone_id');
            $table->dropColumn(['delivery_address', 'preferred_fulfillment_type', 'whatsapp_opt_in']);
        });
    }
};
