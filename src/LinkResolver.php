<?php

namespace Woost\LaravelPrismic;

use Prismic\LinkResolver as PrismicLinkResolver;

class LinkResolver extends PrismicLinkResolver
{

    public function resolve($link): ?string
    {
        if (property_exists($link, 'isBroken') && $link->isBroken === true) {
            // This is for cases when Prismic already knows the link is broken
            abort(404);
        }

        return Facade::routeForTypeName($link->type, $link->uid);
    }

}
