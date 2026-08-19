<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketplaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $autos = MarketplaceCategory::where('slug', 'autos')->firstOrFail();
        Car::with('images')->each(function (Car $car) use ($autos) {
            $listing = MarketplaceListing::updateOrCreate(
                ['slug' => Str::slug("{$car->year}-{$car->make}-{$car->model}-{$car->id}")],
                ['category_id' => $autos->id, 'name' => "{$car->year} {$car->make} {$car->model}", 'description' => $car->description,
                    'price' => $car->price, 'deposit_amount' => $car->deposit_amount, 'condition' => $car->condition,
                    'status' => $car->status === 'available' ? 'active' : $car->status, 'is_featured' => $car->id <= 3, 'published_at' => now()]
            );
            $listing->vehicleDetails()->updateOrCreate([], ['make' => $car->make, 'model' => $car->model, 'year' => $car->year,
                'mileage' => $car->mileage, 'transmission' => $car->transmission, 'fuel_type' => $car->fuel_type,
                'color' => $car->color, 'vin' => $car->vin, 'features' => $car->features]);
            foreach ($car->images as $image) $listing->images()->firstOrCreate(['image_url' => $image->image_url], ['sort_order' => $image->sort_order]);
            $car->update(['marketplace_listing_id' => $listing->id]);
        });

        $this->demoListing('fashion', 'Signature Utility Jacket', 85000, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=1200&q=80',
            true, ['brand' => 'FFSET Select', 'gender' => 'unisex', 'genders' => ['men', 'women', 'unisex'], 'material' => 'Cotton twill', 'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['Black', 'Olive']]);
        $this->demoListing('gadgets', 'Premium Wireless Headphones', 145000, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1200&q=80',
            true, ['brand' => 'FFSET Select', 'model' => 'Studio One', 'warranty' => '12 months', 'specifications' => ['connectivity' => 'Bluetooth 5.3']]);
    }

    private function demoListing(string $categorySlug, string $name, int $price, string $image, bool $is_featured, array $details): void
    {
        $category = MarketplaceCategory::where('slug', $categorySlug)->firstOrFail();
        $listing = MarketplaceListing::updateOrCreate(['slug' => Str::slug($name)], ['category_id' => $category->id, 'name' => $name,
            'short_description' => "A curated {$category->name} selection from FFSET Store.", 'price' => $price, 'condition' => 'new',
            'status' => 'active', 'stock_quantity' => 8, 'is_featured' => $is_featured, 'published_at' => now()]);
        $listing->images()->firstOrCreate(['image_url' => $image], ['alt_text' => $name]);
        if ($categorySlug === 'fashion') {
            $listing->fashionDetails()->updateOrCreate([], $details);
            foreach ($details['genders'] as $gender) foreach ($details['sizes'] as $size) foreach ($details['colors'] as $color) {
                $options = compact('gender', 'size', 'color');
                $listing->variants()->firstOrCreate(['option_key' => MarketplaceListingVariant::optionKey($options)], [
                    'options' => $options, 'sku' => 'JACKET-'.strtoupper("{$gender}-{$size}-{$color}"), 'stock_quantity' => 2, 'is_active' => true,
                ]);
            }
        } else {
            $listing->gadgetDetails()->updateOrCreate([], $details);
        }
    }
}
