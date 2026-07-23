<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    /**
     * Seeded from ffset/lib/site-data.ts `competitionPaymentDetails` and
     * `competitionRules`. The entry fee is backed by a non-stocked
     * "service" product so it flows through the same SaleService as
     * every other sale.
     */
    public function run(): void
    {
        $entryFeeProduct = Product::create([
            'name' => 'FFSET FIFA Championship Entry Fee',
            'type' => 'service',
            'description' => 'Competition registration entry fee.',
            'price' => 5000,
            'is_stocked' => false,
            'status' => 'active',
        ]);

        Competition::create([
            'title' => 'FFSET FIFA Championship',
            'entry_fee_product_id' => $entryFeeProduct->id,
            'entry_fee' => 5000,
            'first_prize' => 100000,
            'second_prize' => 50000,
            'third_prize' => 30000,
            'rules' => [
                'All players must complete registration before the deadline.',
                'Fixtures are single elimination until the final round.',
                'Late arrival beyond 15 minutes counts as a walkover.',
                'Controller disputes are resolved by the event marshals.',
                'Good sportsmanship is required throughout the tournament.',
            ],
            'status' => 'open',
        ]);
    }
}
