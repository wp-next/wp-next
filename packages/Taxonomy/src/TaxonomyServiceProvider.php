<?php

namespace WpNext\Taxonomy;

use Illuminate\Support\ServiceProvider;

class TaxonomyServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('taxonomy', function ($container) {
            return new TaxonomyBuilder($container);
        });
    }

    public function boot()
    {
    }
}
