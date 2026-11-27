<?php

namespace App\Providers;

use App\View\Components\Templates\BreadCrumb;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Herd can serve the local site over HTTPS even when APP_ENV=local.
        // Keep generated form actions and redirects on the same scheme as the
        // current request so the session/CSRF cookie is not split between HTTP
        // and HTTPS.
        if (
            app()->environment('production')
            || str_starts_with((string) env('APP_URL'), 'https://')
            || (app()->bound('request') && request()->isSecure())
        ) {
            URL::forceScheme('https');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Blade::component('templates.breadcrumb', BreadCrumb::class);


    }
}
