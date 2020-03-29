<?php

namespace PragmaRX\Countries\Package\Services\Cache;

use Closure;
use PragmaRX\Countries\Package\Services\Cache\Managers\Nette as NetteManager;
use PragmaRX\Countries\Package\Services\Config;
use Psr\SimpleCache\CacheInterface;

class Service implements CacheInterface
{
    /**
     * Cache.
     *
     * @var object
     */
    protected $manager;

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
     * @param object $manager
     * @param null $path
     */
    public function __construct($config = null, $manager = null, $path = null)
    {
        $this->config = $this->instantiateConfig($config);

        $this->manager = $this->instantiateManager($this->config, $manager, $path);
    }

    /**
     * Instantiate the config.
     *
     * @param $config
     * @return Config|mixed
     */
    public function instantiateConfig($config)
    {
        return \is_null($config) ? new Config() : $config;
    }

    /**
     * Instantiate the cache manager.
     *
     * @param $config
     * @param $manager
     * @param $path
     * @return NetteManager|mixed
     */
    public function instantiateManager($config, $manager, $path)
    {
        return \is_null($manager)
            ? new NetteManager($config, $path)
            : $manager;
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
     * Fetches a value from the cache.
     *
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if ($this->enabled()) {
            return $this->manager->get($key, $default);
        }
    }

    /**
     * Create a cache key.
     *
     * @return string
     * @throws Exception
     */
    public function makeKey()
    {
        $arguments = \func_get_args();

        if (empty($arguments)) {
            throw new Exception('Empty key');
        }

        return base64_encode(serialize($arguments));
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
            return $this->manager->set($key, $value, $ttl);
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
        $this->manager->delete($key);
    }

    /**
     * Wipe clean the entire cache's keys.
     */
    public function clear()
    {
        $this->manager->clear();
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
        return $this->manager->getMultiple($keys, $default);
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
        return $this->manager->setMultiple($keys, $ttl);
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param $keys
     * @return bool|void
     */
    public function deleteMultiple($keys)
    {
        $this->manager->deleteMultiple($keys);
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->manager->has($key);
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
        if (! \is_null($value = $this->manager->get($key))) {
            return $value;
        }

        $this->manager->set($key, $value = $callback(), $minutes);

        return $value;
    }
}
