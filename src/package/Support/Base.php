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
     * Get data directory.
     *
     * @param $path
     * @return string
     */
    protected function dataDir($path = '')
    {
        $path = (empty($path) || starts_with($path, DIRECTORY_SEPARATOR)) ? $path : "/{$path}";

        return __COUNTRIES_DIR__._dir("/src/data$path");
    }

    protected function sanitizeFile($contents)
    {
        return str_replace('\n', '', $contents);
    }

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
     * Load json files from dir.
     *
     * @param $dir
     * @return static
     */
    protected function loadJsonFiles($dir)
    {
        return countriesCollect(glob("$dir/*.json*"))->mapWithKeys(function ($file) {
            $key = str_replace('.json', '', str_replace('.json5', '', basename($file)));

            return [$key => $this->loadJson($file)];
        });
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

    /**
     * Loads a json file.
     *
     * @param $file
     * @param string $dir
     * @return null|string
     * @throws Exception
     */
    public function loadJson($file, $dir = null)
    {
        if (empty($file)) {
            throw new Exception('loadJson Error: File name not set');
        }

        if (! file_exists($file) && ! file_exists($file = $this->dataDir("/$dir/".strtolower($file).'.json'))) {
            return countriesCollect();
        }

        $decoded = json5_decode($this->loadFile($file), true);

        if (is_null($decoded)) {
            throw new Exception("Error decoding json file: $file");
        }

        return countriesCollect($decoded);
    }

    /**
     * Load a file from disk.
     *
     * @param $file
     * @return null|string
     */
    public function loadFile($file)
    {
        if (file_exists($file)) {
            return $this->sanitizeFile(file_get_contents($file));
        }
    }
}
