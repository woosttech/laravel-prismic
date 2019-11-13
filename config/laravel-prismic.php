<?php
return [
    'endpoint' => env('PRISMIC_ENDPOINT'),
    'api_key' => env('PRISMIC_API_KEY'),

    'types' => [],
    'slices' => [],

    'throw_exceptions' => true,
    'cache_prefix' => 'laravel-prismic',
    'route_prefix' => 'laravel-prismic',

    'language_mappings' => [],

    'all_pages_limit' => env('PRISMIC_MAX_RESULTS', 100),
];
