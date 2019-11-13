<?php

namespace Woost\LaravelPrismic;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Prismic\Predicates;

abstract class CustomType implements Arrayable
{

    abstract public static function getRoute(): string;
    abstract public static function getTypeName(): string;
    abstract public static function getViewName(): string;

    public $id;
    public $uid;
    public $href;
    public $tags;
    public $first_publication_date;
    public $last_publication_date;
    public $slugs;
    public $linked_documents;
    public $lang;
    public $alternate_languages;
    public $slices;

    private $data;

    public function __construct($document)
    {
        $this->data = $document->data;

        $this->setMetaData($document);
        $this->setSlices($document->data);
        $this->setCustomData($document->data);
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

    protected static function getDefaultOrderings(): string
    {
        return '[document.first_publication_date desc]';
    }

    protected static function getDefaultLimit(): int
    {
        return 20;
    }

    public static function all(?string $orderings = null, ?array $fields = null)
    {
        return static::where([], $orderings, config('laravel-prismic.all_pages_limit'), 1, $fields);
    }

    public static function getSingle()
    {
        if (!Facade::isEnabled()) return null;

        $options = [];
        $locale = App::getLocale();
        if ($lang = Facade::getPrismicLangForLocale($locale)) {
            $options['lang'] = $lang;
        }

        try {
            $document = Facade::getApi()
                ->getSingle(static::getTypeName(), $options);
        } catch (\Exception $exception) {
            report($exception);
            return null;
        }
            
        if (!$document) return null;

        return new static($document);
    }

    public static function find(string $uid)
    {
        if (!Facade::isEnabled()) return null;

        $options = [];
        $locale = App::getLocale();
        if ($lang = Facade::getPrismicLangForLocale($locale)) {
            $options['lang'] = $lang;
        }

        try {
            $document = Facade::getApi()
                ->getByUID(static::getTypeName(), $uid, $options);
        } catch (\Exception $exception) {
            report($exception);
            return null;
        }
        
        if (!$document) return null;

        return new static($document);
    }

    public static function search(string $query, ?string $orderings = null, ?int $limit = null, ?int $page = null, ?array $fields = null)
    {
        return static::where([
            Predicates::fulltext('document', $query)
        ], $orderings, $limit, $page, $fields);
    }

    public static function where(array $clauses, ?string $orderings = null, ?int $limit = null, ?int $page = null, ?array $fields = null)
    {
        if (!Facade::isEnabled()) return collect();
        
        $options = [];
        $options['orderings'] = $orderings ?? static::getDefaultOrderings();
        $options['pageSize'] = $limit ?? static::getDefaultLimit();
        $options['page'] = $page ?? 1;

        $locale = App::getLocale();
        if ($lang = Facade::getPrismicLangForLocale($locale)) {
            $options['lang'] = $lang;
        }

        if (is_array($fields)) {
            $fields = array_map(function ($field) {
                    return static::getTypeName() . '.' . $field;
                }, $fields);
            $options['fetch'] = implode(',', $fields);
        }

        $predicates = collect($clauses)
            ->map(function ($value, $field) {
                if (is_object($value) && class_implements($value, 'Predicate')) {
                    return $value;
                }
                return Predicates::at($field, $value);
            });
        $predicates->prepend(Predicates::at('document.type', static::getTypeName()));

        try {
            $results = Facade::getApi()
                ->query(
                    $predicates->all(),
                    $options
                )->results;
        } catch (\Exception $e) {
            Log::error("Unable to retrieve Prismic documents: " . $e);
            $results = [];
        }

        $documents = collect($results)
            ->map(function ($document) {
                return new static($document);
            });

        return $documents;
    }

    //// Internals

    protected function setMetaData($document)
    {
        $this->id = $document->id;
        $this->uid = $document->uid;
        $this->href = $document->href;
        $this->tags = $document->tags;
        $this->first_publication_date = new Carbon($document->first_publication_date);
        $this->last_publication_date = new Carbon($document->last_publication_date);
        $this->slugs = $document->slugs;
        $this->linked_documents = $document->linked_documents;
        $this->lang = $document->lang;
        $this->alternate_languages = $document->alternate_languages;
    }

    protected function setSlices($data)
    {
        if (empty($data->body)) return;

        $sliceTypes = collect(config('laravel-prismic.slices'))
            ->mapWithKeys(function ($sliceType) {
                return [$sliceType::getTypeName() => $sliceType];
            })->toArray();

        $this->slices = collect($data->body)
            ->map(function ($sliceData) use ($sliceTypes) {
                if (!array_key_exists($sliceData->slice_type, $sliceTypes)) {
                    if (!config('laravel-prismic.throw_exceptions')) return null;
                    throw new Exceptions\UnknownSliceTypeException($sliceData->slice_type);
                }
                return new $sliceTypes[$sliceData->slice_type]($sliceData);
            })->filter();
    }

    protected function setCustomData($data)
    {
        return;
    }

    //// Arrayable

    public function toArray(): array
    {
        $vars = get_object_vars($this);

        // The following mimics Laravel's `appends` functionality in a simple way
        if (empty($this->appends)) {
            return $vars;
        }

        foreach($this->appends as $key) {
            if (method_exists($this, 'get' . Str::studly($key) . 'Attribute')) {
                $vars[$key] = $this->{'get' . Str::studly($key) . 'Attribute'}();
            }
        }

        return $vars;
    }

}
