<?php

namespace Woost\LaravelPrismic;

use Illuminate\Support\Facades\Cache as LaravelCache;
use Prismic\Cache\CacheInterface;

class Cache implements CacheInterface
{

    public function has($key)
    {
        return LaravelCache::has($this->prefix($key));
    }

    public function get($key)
    {
        return LaravelCache::get($this->prefix($key));
    }

    public function set($key, $value, $ttl = 0)
    {
        return LaravelCache::put($this->prefix($key), $value, $ttl);
    }

    public function delete($key)
    {
        return LaravelCache::forget($this->prefix($key));
    }

    public function clear()
    {
        LaravelCache::flush(config('laravel-prismic.cache_prefix'));
    }

    protected function prefix($key)
    {
        return config('laravel-prismic.cache_prefix') . '.' . $key;
    }

}
