<?php

namespace Woost\LaravelPrismic;

use Illuminate\Support\Facades\Facade as LaravelFacade;

class Facade extends LaravelFacade
{

    protected static function getFacadeAccessor(): string
    {
        return 'laravelprismic';
    }

}
