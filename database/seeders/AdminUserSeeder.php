<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates the first admin login. Credentials come from env so they
     * aren't hardcoded into source; override ADMIN_EMAIL/ADMIN_PASSWORD
     * in .env before seeding on anything beyond local dev.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@ffsetlounge.com')],
            [
                'name' => 'FFSET Admin',
                'password' => env('ADMIN_PASSWORD', 'password'),
                'role' => 'admin',
            ]
        );
    }
}
