<?php

namespace PragmaRX\Countries\Package\Services;

use Exception;
use IlluminateAgnostic\Str\Support\Str;

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
     * Loads a json file.
     *
     * @param $file
     * @param string $dir
     * @return \PragmaRX\Coollection\Package\Coollection
     * @throws Exception
     */
    public function loadJson($file, $dir = null)
    {
        if (empty($file)) {
            throw new Exception('loadJson Error: File name not set');
        }

        if (! file_exists($file) && ! file_exists($file = $this->dataDir("/$dir/".strtolower($file).'.json'))) {
            return coollect();
        }

        $decoded = json5_decode($this->loadFile($file), true);

        if (\is_null($decoded)) {
            throw new Exception("Error decoding json file: $file");
        }

        return coollect($decoded);
    }

    /**
     * Load json files from dir.
     *
     * @param $dir
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function loadJsonFiles($dir)
    {
        return coollect(glob("$dir/*.json*"))->mapWithKeys(function ($file) {
            $key = str_replace(['.json5', '.json'], '', basename($file));

            return [$key => $this->loadJson($file)];
        });
    }

    /**
     * Move files using wildcard filter.
     *
     * @param $from
     * @param $to
     */
    public function moveFilesWildcard($from, $to)
    {
        coollect(glob($this->dataDir($from)))->each(function ($from) use ($to) {
            $this->mkDir($dir = $this->dataDir($to));

            rename($from, $dir.'/'.basename($from));
        });
    }

    /**
     * Get data directory.
     *
     * @param $path
     * @return string
     */
    public function dataDir($path = '')
    {
        $path = (empty($path) || Str::startsWith($path, DIRECTORY_SEPARATOR)) ? $path : "/{$path}";

        return __COUNTRIES_DIR__.$this->toDir("/src/data$path");
    }

    /**
     * @param $contents
     * @return string
     */
    public function sanitizeFile($contents)
    {
        return str_replace('\n', '', $contents);
    }

    /**
     * Check if array is multidimensional.
     *
     * @param $string
     * @return string
     */
    public function toDir($string)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $string);
    }
}
