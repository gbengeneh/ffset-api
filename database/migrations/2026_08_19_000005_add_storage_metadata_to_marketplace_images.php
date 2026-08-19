<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::table('marketplace_listing_images',function(Blueprint $table){$table->string('storage_disk')->nullable();$table->string('storage_path')->nullable();});}public function down():void{Schema::table('marketplace_listing_images',fn(Blueprint $table)=>$table->dropColumn(['storage_disk','storage_path']));}};
