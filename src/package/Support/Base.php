<?php

namespace PragmaRX\Countries\Package\Support;

use Exception;
use \Illuminate\Console\Command;
use PragmaRX\Countries\Package\Service;

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
    protected function message($message, $type = 'line')
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
     * Command setter.
     *
     * @param \Illuminate\Console\Command $command
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
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
            throw new Exception("loadJson Error: File name not set");
        }

        if (!file_exists($file) && !file_exists($file = $this->dataDir("/$dir/".strtolower($file).'.json'))) {
            return countriesCollect();
        }

        $decoded = json5_decode($this->loadFile($file), true);

        if (is_null($decoded)) {
            throw new Exception("Error decoding json file: $file");
        }

        return countriesCollect($decoded);
    }

    /**
     * Loads a json file.
     *
     * @param $file
     * @param string $dir
     * @return null|string
     * @throws Exception
     */
    public function loadCsv($file, $dir = null)
    {
        if (empty($file)) {
            throw new Exception("loadCsv Error: File name not set");
        }

        if (!file_exists($file)) {
            $file = $this->dataDir("/$dir/".strtolower($file).'.csv');
        }

        return countriesCollect(csv_decode(
            file($file),
            true
        ));
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

    /**
     * Make state json filename.
     *
     * @param $key
     * @return string
     */
    protected function makeJsonFileName($key, $dir = '')
    {
        if (! ends_with(DIRECTORY_SEPARATOR, $dir)) {
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
