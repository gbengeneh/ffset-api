<?php

namespace Database\Seeders;

use App\Models\GalleryItem;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    /**
     * Seeded from ffset/lib/site-data.ts `galleryItems`.
     */
    public function run(): void
    {
        $items = [
            ['title' => 'Snooker Lounge', 'category' => 'Interior', 'type' => 'image', 'src' => '/snooker.jpg'],
            ['title' => 'Wine Display', 'category' => 'Wines', 'type' => 'video', 'src' => '/wine.mp4', 'poster' => '/poster-wine.jpg'],
            ['title' => 'Bottle Package Showcase', 'category' => 'Premium Packages', 'type' => 'video', 'src' => '/wine packages.mp4', 'poster' => '/poster-packages.jpg'],
            ['title' => 'Lyta at FFSET Lounge', 'category' => 'Celebrity Visit', 'type' => 'video', 'src' => '/lyta nigeria celebrity in FFset.mp4', 'poster' => '/poster-lyta.jpg'],
            ['title' => 'Meet the Founder', 'category' => 'Leadership', 'type' => 'image', 'src' => '/ceo.jpeg'],
        ];

        foreach ($items as $item) {
            GalleryItem::create($item);
        }
    }
}
