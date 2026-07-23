<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Keep API Resource responses as plain arrays, matching every other
        // endpoint in this API instead of the default {"data": ...} wrapper.
        JsonResource::withoutWrapping();

        // The reset form lives on the Next.js frontend, not a Laravel web route.
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $email = urlencode($user->getEmailForPasswordReset());

            return rtrim(config('app.frontend_url'), '/')."/reset-password?token={$token}&email={$email}";
        });
    }
}
