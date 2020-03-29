<?php

namespace PragmaRX\Countries\Package\Services\Cache\Managers;

use Closure;
use Nette\Caching\Cache as NetteCache;
use Nette\Caching\Storages\FileStorage;
use PragmaRX\Countries\Package\Services\Config;
use Psr\SimpleCache\CacheInterface;

class Nette implements CacheInterface
{
    /**
     * Cache.
     *
     * @var \Nette\Caching\Cache
     */
    protected $cache;

    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Cache directory.
     *
     * @var string
     */
    protected $dir;

    /**
     * Cache constructor.
     * @param object $config
     * @param null $path
     */
    public function __construct($config = null, $path = null)
    {
        $this->config = \is_null($config) ? new Config() : $config;
        $this->cache = new NetteCache($this->getStorage());
    }

    /**
     * Check if cache is enabled.
     *
     * @return bool
     */
    protected function enabled()
    {
        return $this->config->get('countries.cache.enabled');
    }

    /**
     * Get the cache directory.
     *
     * @return mixed|string|static
     */
    public function getCacheDir()
    {
        if (\is_null($this->dir)) {
            $this->dir = $this->config->cache->directory ?: sys_get_temp_dir().'/__PRAGMARX_COUNTRIES__/cache';

            if (! file_exists($this->dir)) {
                mkdir($this->dir, 0755, true);
            }
        }

        return $this->dir;
    }

    /**
     * Get the file storage.
     *
     * @param null $path
     * @return FileStorage
     */
    public function getStorage($path = null)
    {
        return new FileStorage(
            \is_null($path)
                ? $this->getCacheDir()
                : $path
        );
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->enabled()) {
            return $this->cache->load($key, $default);
        }
    }

    /**
     * @param $ttl
     * @return string
     */
    protected function makeExpiration($ttl)
    {
        return ($ttl ?: $this->config->get('cache.duration')).' minutes';
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
        if ($this->enabled()) {
            return $this->cache->save($key, $value, [NetteCache::EXPIRE => $this->makeExpiration($ttl)]);
        }

        return $value;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        $this->cache->remove($key);
    }

    /**
     * Wipe clean the entire cache's keys.
     */
    public function clear()
    {
        $this->cache->clean([NetteCache::ALL => true]);
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
        return coollect($keys)->map(function ($key) {
            return $this->get($key);
        });
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
        return coollect($values)->map(function ($value, $key) use ($ttl) {
            return $this->set($key, $value, $ttl);
        });
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param $keys
     * @return bool|void
     */
    public function deleteMultiple($keys)
    {
        coollect($keys)->map(function ($key) {
            $this->forget($key);
        });
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return ! \is_null($this->get($key));
    }

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param  string $key
     * @param  \DateTimeInterface|\DateInterval|float|int $minutes
     * @param Closure $callback
     * @return mixed
     */
    public function remember($key, $minutes, Closure $callback)
    {
        $value = $this->get($key);

        if (! \is_null($value)) {
            return $value;
        }

        $this->set($key, $value = $callback(), $minutes);

        return $value;
    }
}
