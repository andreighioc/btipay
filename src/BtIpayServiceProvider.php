<?php

namespace AndreiGhioc\BtiPay;

use Illuminate\Support\ServiceProvider;

class BtiPayServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Unește configurația default cu cea a utilizatorului
        $this->mergeConfigFrom(__DIR__.'/../config/btipay.php', 'btipay');

        // Înregistrează clasa principală în Container
        $this->app->singleton('btipay', function ($app) {
            return new BtiPay(config('btipay'));
        });
    }

    public function boot()
    {
        // Permite publicarea configurației: php artisan vendor:publish
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/btipay.php' => config_path('btipay.php'),
            ], 'config');
        }
    }
}
