<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // El sistema usa Bootstrap: sin esto, la paginación sale con la
        // plantilla de Tailwind (flechas gigantes y números sin estilo).
        Paginator::useBootstrapFive();
    }
}
