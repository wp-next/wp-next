<?php

namespace WpNext\TestComponent;

use Illuminate\Support\ServiceProvider;

class TestComponentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources', 'test');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../dist' => resource_path('vendor/google-maps'),
            ], 'google-maps');
        }
    }
}
