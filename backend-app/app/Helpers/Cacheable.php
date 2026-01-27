<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait Cacheable
{
    private ?string $cacheKeyPrefix = null;

    protected function setCachePrefix(string $prefix): self
    {
        $this->cacheKeyPrefix = $prefix;

        return $this;
    }

    protected function setCache(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null): bool
    {
        return Cache::put($this->makeCacheKey($key), $value, $ttl);
    }

    protected function getCache(string $key, mixed $default = null): mixed
    {
        return Cache::get($this->makeCacheKey($key), $default);
    }

    protected function hasCache(string $key): bool
    {
        return Cache::has($this->makeCacheKey($key));
    }

    protected function forgetCache(string $key): bool
    {
        return Cache::forget($this->makeCacheKey($key));
    }

    protected function makeCacheKey(string $key): string
    {
        if (str_contains($key, ':')) {
            return $key;
        }

        $prefix = $this->cacheKeyPrefix ?? Str::slug(class_basename($this), '_');

        return "{$prefix}:{$key}";
    }
}
