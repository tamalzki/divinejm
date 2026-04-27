<?php

namespace App\Providers;

use App\Models\RawMaterial;
use App\Observers\RawMaterialObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        RawMaterial::observe(RawMaterialObserver::class);
    }
}
