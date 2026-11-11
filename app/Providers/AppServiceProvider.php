<?php

namespace App\Providers;

use App\Support\FakeRajaOngkir;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Tangkap query N+1 saat development dan test.
        Model::preventLazyLoading(! $this->app->isProduction());

        if ($this->app->isLocal() && config('services.rajaongkir.fake')) {
            FakeRajaOngkir::register((string) config('services.rajaongkir.base_url'));
        }
    }
}
