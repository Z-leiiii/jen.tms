<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        /*
        |--------------------------------------------------------------------------
        | Force HTTPS in Production
        |--------------------------------------------------------------------------
        |
        | Render serves the application through HTTPS. This makes Laravel
        | generate HTTPS URLs for Vite assets, CSS, JS, routes, etc.
        |
        */
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
