<?php

namespace Florddev\LaravelAutoRouting;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AutoRoutingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(AutoRoute::class, function ($app) {
            return new AutoRoute();
        });
    }

    public function boot()
    {
        Route::macro('auto', function ($prefix, $controller, $options = []) {
            return $this->app->make(AutoRoute::class)->register($prefix, $controller, $options);
        });
    }
}