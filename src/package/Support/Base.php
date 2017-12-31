<?php

namespace PragmaRX\Countries\Package\Support;

use PragmaRX\Countries\Package\Service;

class Base
{
    /**
     * Console command
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
     * Download one or more files.
     *
     * @param $url
     * @param $directory
     */
    protected function download($url, $directory)
    {
        countriesCollect((array) $url)->each(function ($url) use ($directory) {
            $filename = basename($url);

            $destination = _dir("{$directory}/{$filename}");

            $this->message("Downloading to {$destination}");

            $this->mkDir($directory);

            download_file($url, $destination);
        });
    }

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

    /**
     * Display a message in console.
     *
     * @param $message
     * @param string $type
     */
    private function message($message, $type = 'line')
    {
        if (! is_null($this->command)) {
            $this->command->{$type}($message);
        }
    }

    /**
     * Make a directory.
     *
     * @param $dir
     */
    protected function mkDir($dir)
    {
        if (file_exists($dir)) {
            return;
        }

        mkdir($dir, 0755, true);
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
     * Get temp directory.
     *
     * @param string $path
     * @return string
     */
    protected function tmpDir($path)
    {
        return __COUNTRIES_DIR__._dir("/tmp/{$path}");
    }

    /**
     * Loads a json file.
     *
     * @param string $basename
     * @param string $dir
     * @return null|string
     */
    public function loadJson($basename, $dir)
    {
        return $this->loadFile(
            $this->dataDir("/$dir/".strtolower($basename).'.json')
        );
    }

    /**
     * Load a file from disk.
     *
     * @param $file
     * @return null|string
     */
    private function loadFile($file)
    {
        if (file_exists($file)) {
            return file_get_contents($file);
        }
    }

    /**
     * Make state json filename.
     *
     * @param $key
     * @return string
     */
    protected function makeJsonFileName($key, $dir = '')
    {
        if (!ends_with(DIRECTORY_SEPARATOR, $dir)) {
            $dir .= DIRECTORY_SEPARATOR;
        }

        return $this->dataDir(_dir($dir).strtolower($key).'.json');
    }


    /**
     * Put contents into a file.
     *
     * @param $file
     * @param $contents
     */
    public function putFile($file, $contents)
    {
        $this->mkdir(dirname($file));

        file_put_contents($file, $contents);
    }

    /**
     * Encode and pretty print json.
     *
     * @param array $data
     * @return string
     */
    public function jsonEncode($data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Read a file.
     *
     * @param $filePath
     * @return string
     */
    public function readFile($filePath)
    {
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }
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
     * Get package home dir.
     *
     * @return string
     */
    public function getHomeDir()
    {
        return getClassDir(Service::class);
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
}
