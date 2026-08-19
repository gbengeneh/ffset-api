<?php

namespace Database\Seeders;

use App\Models\MarketplaceCategory;
use Illuminate\Database\Seeder;

class MarketplaceCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Autos', 'slug' => 'autos', 'description' => 'Verified vehicles and automotive essentials.', 'sort_order' => 10],
            ['name' => 'Fashion', 'slug' => 'fashion', 'description' => 'Curated clothing, footwear, and accessories.', 'sort_order' => 20],
            ['name' => 'Gadgets', 'slug' => 'gadgets', 'description' => 'Phones, computing, audio, and smart devices.', 'sort_order' => 30],
        ] as $category) MarketplaceCategory::updateOrCreate(['slug' => $category['slug']], $category + ['is_active' => true]);
    }
}
