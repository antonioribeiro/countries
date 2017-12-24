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
        coollect((array) $url)->each(function ($url) use ($directory) {
            $filename = basename($url);

            $destination = _dir("{$directory}/{$filename}");

            $this->command->line("Downloading to {$destination}");

            $this->mkDir($directory);

            file_put_contents($destination, fopen($url, 'r'));
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
