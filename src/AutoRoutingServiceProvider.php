<?php

namespace Florddev\LaravelAutoRouting;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AutoRoutingServiceProvider extends ServiceProvider
{
    protected $commands = [
        'Florddev\LaravelAutoRouting\Console\ExtendedControllerMakeCommand',
    ];

    public function register()
    {
        $this->commands($this->commands);

        $this->app->singleton(AutoRoute::class, function ($app) {
            return new AutoRoute();
        });
    }

    public function boot()
    {
        Route::macro('auto', function ($prefix, $controller, $options = []) {
            return app(AutoRoute::class)->register($prefix, $controller, $options);
        });
    }
}
