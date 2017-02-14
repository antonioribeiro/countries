<?php

namespace PragmaRX\Countries\Support;

use Exception;
use Psr\SimpleCache\CacheInterface;

class Cache implements CacheInterface
{
    /**
     * Fetches a value from the cache.
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return cache($key, $default);
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        return cache()->put($key, $value, $ttl ?: config('countries.cache.duration'));
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        return cache()->forget($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     */
    public function clear()
    {
        return cache()->flush();
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param $keys
     * @param null $default
     * @return array
     */
    public function getMultiple($keys, $default = null)
    {
        return cache()->many($keys);
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param $values
     * @param null $ttl
     * @return bool
     */
    public function setMultiple($values, $ttl = null)
    {
        return cache()->putMany($values, $ttl ?: config('countries.cache.duration'));
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param $keys
     * @return bool|void
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            cache()->forget($key);
        }
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return cache()->has($key);
    }

    /**
     * Create a cache key.
     *
     * @return string
     * @throws Exception
     */
    public function makeKey()
    {
        $arguments = func_get_args();

        if (empty($arguments)) {
            throw new Exception('Empty key');
        }

        return base64_encode(serialize($arguments));
    }
}
