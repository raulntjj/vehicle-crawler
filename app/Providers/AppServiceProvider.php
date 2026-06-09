<?php

namespace App\Providers;

use App\Services\ETL\Contracts\VehicleETLInterface;
use App\Services\ETL\Contracts\VehicleRepositoryInterface;
use App\Services\ETL\Contracts\VehicleTransformerInterface;
use App\Services\ETL\VehicleETLService;
use App\Services\ETL\VehicleRepository;
use App\Services\ETL\VehicleTransformer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VehicleETLInterface::class, VehicleETLService::class);
        $this->app->bind(VehicleTransformerInterface::class, VehicleTransformer::class);
        $this->app->bind(VehicleRepositoryInterface::class, VehicleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

