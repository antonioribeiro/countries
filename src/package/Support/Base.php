<?php

namespace PragmaRX\Countries\Package\Support;

class Base
{
    protected $command;

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
     * @param string $path
     * @return string
     */
    protected function dataDir($path = '')
    {
        return __COUNTRIES_DIR__._dir("/src/data{$path}");
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
     * Get temp directory.
     *
     * @param string $path
     * @return string
     */
    protected function tmpDir($path)
    {
        return __COUNTRIES_DIR__._dir("/tmp/{$path}");
    }
}
