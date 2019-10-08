<?php

namespace Woost\LaravelPrismic;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-prismic.php', 'laravel-prismic'
        );

        $this->app->bind('laravelprismic', function () {
            return new Service();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(
            __DIR__ . '/routes.php'
        );
        $this->publishes([
            __DIR__ . '/../config/laravel-prismic.php' => config_path('laravel-prismic.php'),
        ]);
    }

}
