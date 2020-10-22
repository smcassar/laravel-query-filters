<?php

namespace SMCassar\LaravelQueryFilters\Filters\Concerns;

use Illuminate\Support\Facades\Cache;

trait FetchesResults
{
    protected $shouldCache = false;

    protected $ttl = 60 * 60;

    protected array $perPageOptions = [10, 20, 50, 100];

    public function noCache()
    {
        $this->shouldCache = false;

        return $this;
    }

    public function cache($ttl = 60 * 60)
    {
        $this->shouldCache = true;
        $this->ttl = $ttl;

        return $this;
    }

    public function get(...$args)
    {
        return $this->getCachableResults('get', $args);
    }

    public function paginate(...$args)
    {
        return $this->getCachableResults('paginate', $args);
    }

    public function smartPaginate($perPage = null, ...$args)
    {
        $requestedPerPage = $this->request->input('per_page');
        if (in_array($requestedPerPage, $this->perPageOptions)) {
            $perPage = $requestedPerPage;
        }

        return $this->paginate($perPage, ...$args);
    }

    protected function getCachableResults(string $method, array $args = [])
    {
        if (!$this->shouldCache) {
            return call_user_func_array([$this->builder, $method], $args);
        }

        $class = class_basename(static::class);
        Cache::tags(['query-filter', $class])->remember($this->getCacheKey($class, $method), $this->ttl, function () use ($method, $args) {
            return call_user_func_array([$this->builder, $method], $args);
        });
    }

    protected function getCacheKey(string $class, string $method): string
    {
        $hashValues = $this->getFilters()->merge(['class' => $class, 'method' => $method]);

        return hash('sha512', $hashValues->toJson());
    }
}
