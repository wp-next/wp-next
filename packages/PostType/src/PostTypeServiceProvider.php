<?php

namespace WpNext\PostType;

use Illuminate\Support\ServiceProvider;

class PostTypeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('postType', function ($container) {
            return new PostTypeBuilder($container);
        });
    }
}
