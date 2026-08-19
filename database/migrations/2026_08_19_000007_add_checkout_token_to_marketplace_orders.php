<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::table('marketplace_orders',fn(Blueprint $table)=>$table->uuid('checkout_token')->nullable()->unique());}public function down():void{Schema::table('marketplace_orders',fn(Blueprint $table)=>$table->dropColumn('checkout_token'));}};
