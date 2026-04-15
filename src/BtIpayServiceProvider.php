<?php

namespace AndreiGhioc\BtiPay;

use Illuminate\Support\ServiceProvider;

class BtiPayServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge default configuration with user's configuration
        $this->mergeConfigFrom(__DIR__.'/../config/btipay.php', 'btipay');

        // Register the main class in the Container through its Interface
        $this->app->singleton(\AndreiGhioc\BtiPay\Contracts\BtiPayGatewayInterface::class, function ($app) {
            return new BtiPay(config('btipay'));
        });
        
        $this->app->alias(\AndreiGhioc\BtiPay\Contracts\BtiPayGatewayInterface::class, 'btipay');
    }

    public function boot()
    {
        // Allow publishing the configuration file: php artisan vendor:publish
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/btipay.php' => config_path('btipay.php'),
            ], 'config');
        }
    }
}
