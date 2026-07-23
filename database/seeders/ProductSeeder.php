<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Wines and gaming packages seeded from ffset/lib/site-data.ts so the
     * admin starts with real, editable catalog rows instead of empty tables.
     */
    public function run(): void
    {
        $wines = [
            ['name' => 'Dom Perignon Vintage', 'category' => 'Champagne', 'description' => 'A prestige celebration bottle with bright citrus depth and creamy finish.', 'size' => '750ml', 'availability' => 'Available', 'image_url' => '/dom-perignon.jpg', 'price' => 180000],
            ['name' => 'Hennessy XO', 'category' => 'Cognac', 'description' => 'Layered oak spice and velvet warmth for premium late-night sipping.', 'size' => '700ml', 'availability' => 'Limited Stock', 'image_url' => '/hennessy-xo.jpg', 'price' => 150000],
            ['name' => 'Moet & Chandon Nectar', 'category' => 'Sparkling', 'description' => 'Lush fruit notes with a glamorous party profile and smooth sweetness.', 'size' => '750ml', 'availability' => 'Available', 'image_url' => 'https://unwindbottleshop.com/cdn/shop/products/MOET_NECTAR_IMPERIAL.png?v=1710057823&width=1445', 'price' => 95000],
            ['name' => 'Don Julio 1942', 'category' => 'Tequila', 'description' => 'Elegant agave character with a polished, collector-worthy finish.', 'size' => '750ml', 'availability' => 'Reserve Only', 'image_url' => '/don-julio-1942.jpg', 'price' => 250000],
            ['name' => 'Chateau Margaux Reserve', 'category' => 'Red Wine', 'description' => 'Full-bodied structure for guests who want heritage and statement pours.', 'size' => '750ml', 'availability' => 'Available', 'image_url' => '/red-wine-reserve.jpg', 'price' => 200000],
            ['name' => 'Veuve Clicquot Brut', 'category' => 'Champagne', 'description' => 'Crisp luxury bubbles suited for brunch, birthdays, and bold entrances.', 'size' => '750ml', 'availability' => 'Available', 'image_url' => 'https://static1.aporvino.com/4268-thickbox_default/veuve-clicquot-brut-yellow-label.jpg', 'price' => 85000],
        ];

        foreach ($wines as $wine) {
            [$quantity, $status] = match ($wine['availability']) {
                'Available' => [20, 'active'],
                'Limited Stock' => [4, 'active'],
                'Reserve Only' => [0, 'reserve_only'],
            };

            Product::create([
                'name' => $wine['name'],
                'type' => 'wine',
                'category' => $wine['category'],
                'description' => $wine['description'],
                'size' => $wine['size'],
                'price' => $wine['price'],
                'image_url' => $wine['image_url'],
                'is_stocked' => true,
                'stock_quantity' => $quantity,
                'low_stock_threshold' => 5,
                'status' => $status,
            ]);
        }

        $gamingPackages = [
            ['name' => 'After Work Rush', 'description' => 'One console bay, drinks-ready seating, and two hours of peak-hour play.', 'price' => 18000],
            ['name' => 'Squad Night Bundle', 'description' => 'Multi-player setup for friends with shared platter and bottle upgrade path.', 'price' => 42000],
            ['name' => 'Tournament Bay', 'description' => 'Bracket-ready screen setup for challenge nights and competition warmups.', 'price' => 55000],
        ];

        foreach ($gamingPackages as $package) {
            Product::create([
                'name' => $package['name'],
                'type' => 'gaming_package',
                'description' => $package['description'],
                'price' => $package['price'],
                'is_stocked' => false,
                'status' => 'active',
            ]);
        }
    }
}
