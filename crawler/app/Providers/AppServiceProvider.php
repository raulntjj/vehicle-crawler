<?php

namespace App\Providers;

use App\Repositories\BrandRepository;
use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Repositories\Contracts\RawVehicleRepositoryInterface;
use App\Repositories\Contracts\VehicleRepositoryInterface;
use App\Repositories\RawVehicleRepository;
use App\Repositories\VehicleRepository;
use App\Services\ETL\Contracts\VehicleTransformerInterface;
use App\Services\ETL\VehicleTransformer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(BrandRepositoryInterface::class, BrandRepository::class);
        $this->app->bind(RawVehicleRepositoryInterface::class, RawVehicleRepository::class);
        $this->app->bind(VehicleRepositoryInterface::class, VehicleRepository::class);

        // ETL Services
        $this->app->bind(VehicleTransformerInterface::class, VehicleTransformer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
