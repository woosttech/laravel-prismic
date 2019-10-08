<?php

namespace Woost\LaravelPrismic;

abstract class Slice
{

    abstract public static function getTypeName(): string;
    abstract public static function getViewName(): string;

    private $data;

    public function __construct($slice)
    {
        $this->data = $slice->primary;

        $this->setNonRepeatable($slice->primary);
        $this->setRepeatable($slice->items);
    }

    public function __get(string $key)
    {
        if ($this->data->{$key}) {
            return $this->data->{$key};
        }

        if (method_exists($this, 'get' . Str::studly($key) . 'Attribute')) {
            return $this->{'get' . Str::studly($key) . 'Attribute'}();
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $key .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);

        return null;
    }

    protected function setNonRepeatable($nonRepeatable)
    {
        return;
    }

    protected function setRepeatable($repeatable)
    {
        return;
    }
}
