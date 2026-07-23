<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Seeded from ffset/lib/site-data.ts `events`.
     */
    public function run(): void
    {
        $events = [
            ['title' => 'DJ Night', 'date' => 'Every Friday', 'frequency' => 'Weekly', 'description' => 'Deep lounge energy with a curated sound palette and late-night momentum.', 'icon' => 'music', 'image_url' => 'https://images.unsplash.com/photo-1750700383190-85b2a6626916?auto=format&fit=crop&w=1200&q=80'],
            ['title' => 'Game Night', 'date' => 'Every Saturday', 'frequency' => 'Weekly', 'description' => 'Competitive console rotations, bragging rights, and a packed crowd vibe.', 'icon' => 'controller', 'image_url' => '/game-night.jpg'],
            ['title' => 'Wine Tasting', 'date' => 'First Sunday Monthly', 'frequency' => 'Monthly', 'description' => 'Guided premium selections with pairing notes and exclusive reserve previews.', 'icon' => 'wine', 'image_url' => 'https://images.unsplash.com/photo-1685461936207-f4b86fe7fcf4?auto=format&fit=crop&w=1200&q=80'],
            ['title' => 'Birthday Hangout', 'date' => 'On Request', 'frequency' => 'On Request', 'description' => 'Custom setup for intimate celebrations with premium service options.', 'icon' => 'cake', 'image_url' => 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?auto=format&fit=crop&w=1200&q=80'],
            ['title' => 'Football Viewing Night', 'date' => 'Match Days', 'frequency' => 'Seasonal', 'description' => 'Big-screen football, sharp sound, and a social atmosphere built for drama.', 'icon' => 'trophy', 'image_url' => 'https://images.unsplash.com/photo-1671368913134-c211bc02487f?auto=format&fit=crop&w=1200&q=80'],
        ];

        foreach ($events as $event) {
            Event::create($event);
        }
    }
}
