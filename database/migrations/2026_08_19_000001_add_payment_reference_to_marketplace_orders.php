<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('marketplace_orders', fn(Blueprint $table)=>$table->string('payment_reference')->nullable()->unique()->after('payment_status')); }
    public function down(): void { Schema::table('marketplace_orders', fn(Blueprint $table)=>$table->dropColumn('payment_reference')); }
};
