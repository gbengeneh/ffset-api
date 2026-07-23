<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CashierUserSeeder extends Seeder
{
    /**
     * Creates a demo cashier login, mirroring AdminUserSeeder's env-driven
     * pattern so there's a working POS account out of the box.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('CASHIER_EMAIL', 'cashier@ffsetlounge.com')],
            [
                'name' => 'FFSET Cashier',
                'password' => env('CASHIER_PASSWORD', 'password'),
                'role' => User::ROLE_CASHIER,
            ]
        );
    }
}
