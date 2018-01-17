<?php

namespace PragmaRX\Countries\Package\Support;

class Base
{
    /**
     * Console command.
     *
     * @var \Illuminate\Console\Command
     */
    protected $command;

    /**
     * Cache instance.
     *
     * @var \PragmaRX\Countries\Package\Support\Cache
     */
    public $cache;

    /**
     * Cache setter.
     *
     * @param Cache $cache
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get a cached value.
     *
     * @param $array
     * @return bool|mixed
     */
    public function getCached($array)
    {
        if (config('countries.cache.enabled')) {
            if ($value = $this->cache->get($this->cache->makeKey($array))) {
                return $value;
            }
        }

        return false;
    }

    /**
     * Cache a value.
     *
     * @param $keyParameters
     * @param $value
     * @return mixed
     */
    public function cache($keyParameters, $value)
    {
        if (config('countries.cache.enabled')) {
            $this->cache->set($this->cache->makeKey($keyParameters), $value);
        }

        return $value;
    }

    /**
     * Make a collection.
     *
     * @param $country
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function collection($country)
    {
        return countriesCollect($country);
    }
}
