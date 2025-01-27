<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use App\Services\TransactionService;
use App\Services\BalanceService;
use App\Repositories\AccountRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register repositories as singletons
        $this->app->singleton(AccountRepository::class);
        $this->app->singleton(TransactionRepository::class);

        // Register services with their dependencies
        $this->app->singleton(TransactionService::class, function ($app) {
            return new TransactionService(
                $app->make(TransactionRepository::class),
                $app->make(AccountRepository::class)
            );
        });

        $this->app->singleton(BalanceService::class, function ($app) {
            return new BalanceService(
                $app->make(AccountRepository::class),
                $app->make(TransactionRepository::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure default string length for database
        Schema::defaultStringLength(191);

        // Remove data wrapping from API resources
        JsonResource::withoutWrapping();

        // Configure global rate limiting
        $this->configureRateLimiting();

        // Set default currency
        config(['app.currency' => env('DEFAULT_CURRENCY', 'USD')]);

        // Only set cache prefix if not using array driver
        if (Cache::getStore() instanceof \Illuminate\Cache\ArrayStore) {
            // Skip prefix for array driver
            return;
        }
        Cache::setPrefix('tps_');
    }

    /**
     * Configure global rate limiting.
     */
    protected function configureRateLimiting(): void
    {
        $this->app['config']->set('sanctum.limiters', [
            'api' => [
                'max_attempts' => env('API_RATE_LIMIT', 60),
                'decay_minutes' => 1
            ],
            'high_value' => [
                'max_attempts' => env('HIGH_VALUE_RATE_LIMIT', 10),
                'decay_minutes' => 1
            ]
        ]);
    }
}
