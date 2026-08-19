<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CashierUserSeeder::class,
            ProductSeeder::class,
            EventSeeder::class,
            CompetitionSeeder::class,
            GallerySeeder::class,
            CarSeeder::class,
            MarketplaceCategorySeeder::class,
            MarketplaceDemoSeeder::class,
            MarketplaceDeliveryZoneSeeder::class,
        ]);
    }
}
