<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_fashion_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained('marketplace_listings')->cascadeOnDelete();
            $table->string('brand')->nullable(); $table->string('gender')->nullable();
            $table->string('material')->nullable(); $table->json('sizes')->nullable(); $table->json('colors')->nullable();
            $table->timestamps();
        });
        Schema::create('marketplace_gadget_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained('marketplace_listings')->cascadeOnDelete();
            $table->string('brand')->nullable(); $table->string('model')->nullable(); $table->string('storage')->nullable();
            $table->string('memory')->nullable(); $table->string('warranty')->nullable(); $table->json('specifications')->nullable();
            $table->timestamps();
        });
        Schema::table('cars', fn (Blueprint $table) => $table->foreignId('marketplace_listing_id')->nullable()->unique()->constrained('marketplace_listings')->nullOnDelete());

        foreach ([
            ['name' => 'Autos', 'slug' => 'autos', 'description' => 'Verified vehicles and automotive essentials.', 'sort_order' => 10],
            ['name' => 'Fashion', 'slug' => 'fashion', 'description' => 'Curated clothing, footwear, and accessories.', 'sort_order' => 20],
            ['name' => 'Gadgets', 'slug' => 'gadgets', 'description' => 'Phones, computing, audio, and smart devices.', 'sort_order' => 30],
        ] as $category) {
            if (!DB::table('marketplace_categories')->where('slug', $category['slug'])->exists()) {
                DB::table('marketplace_categories')->insert($category + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        $autosId = DB::table('marketplace_categories')->where('slug', 'autos')->value('id');
        DB::table('cars')->orderBy('id')->each(function ($car) use ($autosId) {
            $slug = Str::slug("{$car->year}-{$car->make}-{$car->model}-{$car->id}");
            $listingId = DB::table('marketplace_listings')->insertGetId([
                'category_id' => $autosId, 'name' => "{$car->year} {$car->make} {$car->model}", 'slug' => $slug,
                'description' => $car->description, 'price' => $car->price, 'deposit_amount' => $car->deposit_amount,
                'condition' => $car->condition, 'status' => match ($car->status) { 'available' => 'active', default => $car->status },
                'is_featured' => false, 'published_at' => $car->created_at, 'created_at' => $car->created_at, 'updated_at' => $car->updated_at,
            ]);
            DB::table('marketplace_vehicle_details')->insert([
                'listing_id' => $listingId, 'make' => $car->make, 'model' => $car->model, 'year' => $car->year,
                'mileage' => $car->mileage, 'transmission' => $car->transmission, 'fuel_type' => $car->fuel_type,
                'color' => $car->color, 'vin' => $car->vin, 'features' => $car->features,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('car_images')->where('car_id', $car->id)->orderBy('sort_order')->each(fn ($image) => DB::table('marketplace_listing_images')->insert([
                'listing_id' => $listingId, 'image_url' => $image->image_url, 'sort_order' => $image->sort_order,
                'created_at' => $image->created_at, 'updated_at' => $image->updated_at,
            ]));
            DB::table('cars')->where('id', $car->id)->update(['marketplace_listing_id' => $listingId]);
        });
    }

    public function down(): void
    {
        Schema::table('cars', fn (Blueprint $table) => $table->dropConstrainedForeignId('marketplace_listing_id'));
        Schema::dropIfExists('marketplace_gadget_details');
        Schema::dropIfExists('marketplace_fashion_details');
    }
};
