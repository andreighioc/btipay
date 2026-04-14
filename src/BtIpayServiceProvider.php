<?php

namespace BtIpay\Laravel;

use Illuminate\Support\ServiceProvider;
use BtIpay\Laravel\Console\InstallCommand;

class BtIpayServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/btipay.php',
            'btipay'
        );

        $this->app->singleton(BtIpayClient::class, function ($app) {
            return new BtIpayClient(
                config('btipay.username'),
                config('btipay.password'),
                config('btipay.environment', 'sandbox'),
                config('btipay.auth_method', 'header'),
                config('btipay.http', [])
            );
        });

        $this->app->singleton(BtIpayGateway::class, function ($app) {
            return new BtIpayGateway(
                $app->make(BtIpayClient::class)
            );
        });

        $this->app->alias(BtIpayGateway::class, 'btipay');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/btipay.php' => config_path('btipay.php'),
            ], 'btipay-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'btipay-migrations');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
