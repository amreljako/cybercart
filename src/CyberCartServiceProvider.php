<?php

namespace Amreljako\CyberCart;

use Illuminate\Support\ServiceProvider;
use Amreljako\CyberCart\Services\CartService;
use Amreljako\CyberCart\Services\CheckoutService;
use Amreljako\CyberCart\Services\Payment\PaymentManager;

class CyberCartServiceProvider extends ServiceProvider
{
    /**
     * Register any application services into the local Service Container.
     */
    public function register(): void
    {
        // Merge package configurations safely
        $this->mergeConfigFrom(__DIR__.'/../config/cybercart.php', 'cybercart');

        // Bind core cart engine as a structural Singleton pattern
        $this->app->singleton('cybercart.cart', function ($app) {
            return new CartService();
        });

        // Bind Payment Manager Driver registry wrapper
        $this->app->singleton(PaymentManager::class, function ($app) {
            return new PaymentManager($app);
        });

        // Bind Checkout execution engine logic
        $this->app->singleton('cybercart.checkout', function ($app) {
            return new CheckoutService($app->make(PaymentManager::class));
        });
    }

    /**
     * Bootstrap any package specific application lifecycle procedures.
     */
    public function boot(): void
    {
        // 1. Load the internal demo web routes globally so they work in the browser
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        // 2. Load migrations globally so Laravel knows where they are during standard runtime
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // 3. Keep ONLY the assets publishing strictly inside the console-only boundary
        if ($this->app->runningInConsole()) {
            // Allow developers to publish config files using 'php artisan vendor:publish'
            $this->publishes([
                __DIR__.'/../config/cybercart.php' => config_path('cybercart.php'),
            ], 'cybercart-config');
        }
    }
}