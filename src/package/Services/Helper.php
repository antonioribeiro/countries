<?php

namespace PragmaRX\Countries\Package\Services;

use Exception;
use Illuminate\Support\Str;

class Helper
{
    /**
     * @var object
     */
    protected $config;

    /**
     * Rinvex constructor.
     *
     * @param object $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Load a file from disk.
     *
     * @param string $file
     *
     * @return null|string
     */
    public function loadFile(string $file)
    {
        if (!file_exists($file)) {
            return null;
        }

        return $this->sanitizeFile(file_get_contents($file));
    }

    /**
     * Loads a json file.
     *
     * @param        string $file
     * @param string|null $dir
     *
     * @throws Exception
     *
     * @return \Illuminate\Support\Collection
     */
    public function loadJson(string $file, ?string $dir = null)
    {
        if (empty($file)) {
            throw new Exception('loadJson Error: File name not set');
        }

        if (!file_exists($file) && !file_exists($file = $this->dataDir("/$dir/" . strtolower($file) . '.json'))) {
            return countriesCollect();
        }

        $decoded = json5_decode($this->loadFile($file), true);

        if (\is_null($decoded)) {
            throw new Exception("Error decoding json file: $file");
        }

        return countriesCollect($decoded);
    }

    /**
     * Load json files from dir.
     *
     * @param string $dir
     *
     * @return \Illuminate\Support\Collection
     */
    public function loadJsonFiles(string $dir)
    {
        return countriesCollect(glob("$dir/*.json*"))->mapWithKeys(function ($file) {
            $key = str_replace(['.json5', '.json'], '', basename($file));

            return [$key => $this->loadJson($file)];
        });
    }

    /**
     * Move files using wildcard filter.
     *
     * @param string $from
     * @param string $to
     */
    public function moveFilesWildcard(string $from, string $to): void
    {
        countriesCollect(glob($this->dataDir($from)))->each(function ($from) use ($to) {
            $this->makeDir($dir = $this->dataDir($to));

            rename($from, $dir . '/' . basename($from));
        });
    }

    /**
     * Get data directory.
     *
     * @param string $path
     *
     * @return string
     */
    public function dataDir(string $path = ''): string
    {
        $path = empty($path) || Str::startsWith($path, DIRECTORY_SEPARATOR) ? $path : "/{$path}";

        if (!\defined('__COUNTRIES_DIR__')) {
            $dir = '';
        } else {
            $dir = __COUNTRIES_DIR__;
        }

        return $dir . $this->toDir("/src/data$path");
    }

    /**
     * @param string $contents
     *
     * @return string
     */
    public function sanitizeFile(string $contents)
    {
        return str_replace('\n', '', $contents);
    }

    /**
     * Check if array is multidimensional.
     *
     * @param string $string
     *
     * @return string
     */
    public function toDir(string $string)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $string);
    }

    protected function makeDir(string $param): void
    {
        @mkdir($param, 0777, true);
    }
}
