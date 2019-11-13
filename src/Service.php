<?php

namespace Woost\LaravelPrismic;

use Prismic\Api;
use Prismic\Cache\NoCache;
use Prismic\Dom\RichText;

class Service
{

    protected $api;
    protected $linkResolver;
    protected $cache;

    public function isEnabled(): bool
    {
        return !!config('laravel-prismic.endpoint') && !!config('laravel-prismic.api_key');
    }

    public function __construct()
    {
        $endpoint = config('laravel-prismic.endpoint');
        $apiKey = config('laravel-prismic.api_key');

        if (empty($endpoint) || empty($apiKey)) {
            throw new Exceptions\NotConfiguredException('Please setup Prismic configuration in the .env file');
        }

        $this->cache = config('app.debug') ? new NoCache : new Cache;
        $this->linkResolver = new LinkResolver;
        $this->api = Api::get($endpoint, $apiKey, null, $this->cache);
    }

    public function getApi()
    {
        return $this->api;
    }

    public function getLinkResolver()
    {
        return $this->linkResolver;
    }

    public static function linkResolver()
    {
        return new PrismicLinkResolver;
    }

    public function getPrismicLangForLocale($locale)
    {
        $mapping = config('laravel-prismic.language_mappings');

        if (!isset($mapping[$locale])) return null;
        return $mapping[$locale];
    }

    public static function classForTypeName(string $typeName)
    {
        foreach (config('laravel-prismic.types') as $type) {
            if ($type::getTypeName() === $typeName) {
                return $type;
            }
        }

        return null;
    }

    public static function routeForTypeName(string $typeName, string $uid): string
    {
        $type = Facade::classForTypeName($typeName);
        if (!$type) {
            throw new UnknownTypeException($typeName);
        }

        return route(config('laravel-prismic.route_prefix') . '.' . $type::getTypeName() . '.show', [
            'uid' => $uid,
        ]);
    }

    public function parseRichText($richText): string
    {
        return RichText::asHtml($richText, $this->linkResolver);
    }

}
