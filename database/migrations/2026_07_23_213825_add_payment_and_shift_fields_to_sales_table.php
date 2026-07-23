<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->unique()->after('payment_method');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->foreignId('cash_shift_id')->nullable()->after('staff_id')->constrained('cash_shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_shift_id');
            $table->dropColumn(['payment_reference', 'customer_email']);
        });
    }
};
