<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('marketplace_fashion_details', fn(Blueprint $table)=>$table->json('genders')->nullable()->after('gender'));
        DB::table('marketplace_fashion_details')->whereNotNull('gender')->orderBy('id')->each(fn($detail)=>DB::table('marketplace_fashion_details')->where('id',$detail->id)->update(['genders'=>json_encode([$detail->gender])]));
    }
    public function down(): void { Schema::table('marketplace_fashion_details', fn(Blueprint $table)=>$table->dropColumn('genders')); }
};
