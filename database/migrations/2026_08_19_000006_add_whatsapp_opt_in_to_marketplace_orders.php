<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::table('marketplace_orders',fn(Blueprint $table)=>$table->boolean('whatsapp_opt_in')->default(false)->after('phone'));}public function down():void{Schema::table('marketplace_orders',fn(Blueprint $table)=>$table->dropColumn('whatsapp_opt_in'));}};
