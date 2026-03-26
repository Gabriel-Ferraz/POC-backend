<?php

declare(strict_types=1);

use App\Helpers\Trait\Cacheable;

// Named class to avoid Str::slug() deprecation warnings triggered by anonymous class names
final class CacheableTestDouble
{
    use Cacheable;

    public function exposedMakeCacheKey(string $key): string
    {
        return $this->makeCacheKey($key);
    }

    public function exposedSetCachePrefix(string $prefix): static
    {
        return $this->setCachePrefix($prefix);
    }

    public function exposedSetCache(string $key, mixed $value, mixed $ttl = null): bool
    {
        return $this->setCache($key, $value, $ttl);
    }

    public function exposedGetCache(string $key, mixed $default = null): mixed
    {
        return $this->getCache($key, $default);
    }

    public function exposedHasCache(string $key): bool
    {
        return $this->hasCache($key);
    }

    public function exposedForgetCache(string $key): bool
    {
        return $this->forgetCache($key);
    }
}

describe('Cacheable trait', function () {
    it('makeCacheKey auto-prefixes using class basename when no prefix set', function () {
        $obj = new CacheableTestDouble();
        $key = $obj->exposedMakeCacheKey('my-key');

        // CacheableTestDouble → Str::slug lowercases without word separators
        expect($key)->toBe('cacheabletestdouble:my-key');
    });

    it('makeCacheKey uses custom prefix after setCachePrefix', function () {
        $obj = new CacheableTestDouble();
        $obj->exposedSetCachePrefix('custom');

        expect($obj->exposedMakeCacheKey('my-key'))->toBe('custom:my-key');
    });

    it('makeCacheKey returns key as-is when it already contains a colon', function () {
        $obj = new CacheableTestDouble();

        expect($obj->exposedMakeCacheKey('already:prefixed'))->toBe('already:prefixed');
    });

    it('setCache stores and getCache retrieves a value', function () {
        $obj = new CacheableTestDouble();
        $obj->exposedSetCachePrefix('test');
        $obj->exposedSetCache('foo', 'bar');

        expect($obj->exposedGetCache('foo'))->toBe('bar');
    });

    it('getCache returns default when key does not exist', function () {
        $obj = new CacheableTestDouble();
        $obj->exposedSetCachePrefix('test');

        expect($obj->exposedGetCache('missing', 'default-value'))->toBe('default-value');
    });

    it('hasCache returns true when key exists', function () {
        $obj = new CacheableTestDouble();
        $obj->exposedSetCachePrefix('test');
        $obj->exposedSetCache('exists', true);

        expect($obj->exposedHasCache('exists'))->toBeTrue();
    });

    it('hasCache returns false when key does not exist', function () {
        $obj = new CacheableTestDouble();
        $obj->exposedSetCachePrefix('test');

        expect($obj->exposedHasCache('not-there'))->toBeFalse();
    });

    it('forgetCache removes the cached value', function () {
        $obj = new CacheableTestDouble();
        $obj->exposedSetCachePrefix('test');
        $obj->exposedSetCache('removable', 'value');
        $obj->exposedForgetCache('removable');

        expect($obj->exposedHasCache('removable'))->toBeFalse();
    });

    it('setCachePrefix returns self for fluent chaining', function () {
        $obj = new CacheableTestDouble();
        $result = $obj->exposedSetCachePrefix('chain');

        expect($result)->toBe($obj);
    });
});
