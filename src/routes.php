<?php
$types = config('laravel-prismic.types');

foreach ($types as $type) {
    Route::get($type::getRoute(), 'Woost\LaravelPrismic\Controller@show')
        ->name(config('laravel-prismic.route_prefix') . '.' . $type::getTypeName() . '.show');
}
