<?php

use PragmaRX\Countries\Package\Support\Collection;

if (! function_exists('getPackageSrcDir')) {
    /**
     * Get package src directory.
     *
     * @param $class
     * @return string
     */
    function getPackageSrcDir($class)
    {
        $dir = getClassDir($class);

        $depth = 0;

        while (! file_exists($dir._dir('/composer.json')) && $depth < 16) {
            $dir .= _dir('/..');

            $depth++;
        }

        return $dir;
    }
}

if (! function_exists('getClassDir')) {
    /**
     * Get class directory.
     *
     * @param $class
     * @return string
     */
    function getClassDir($class)
    {
        $reflector = new ReflectionClass($class);

        return dirname($reflector->getFileName());
    }
}

if (! function_exists('_dir')) {
    /**
     * Check if array is multidimensional.
     *
     * @param $string
     * @return string
     */
    function _dir($string)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $string);
    }
}

if (! function_exists('countriesCollect')) {
    /**
     * Check if array is multidimensional.
     *
     * @param mixed|null $data
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    function countriesCollect($data = null)
    {
        return new Collection($data);
    }
}

if (! function_exists('download_file')) {
    /**
     * Download a file from the Internet.
     *
     * @param $url
     * @param $destination
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    function download_file($url, $destination)
    {
        $fr = fopen($url, 'r');
        $fw = fopen($destination, 'w');

        while (! feof($fr)) {
            fwrite($fw, fread($fr, 4096));

            flush();
        }

        fclose($fr);
        fclose($fw);

        chmod($destination, 0644);
    }
}

if (! function_exists('deltree')) {
    /**
     * Delete a directory and all its files.
     *
     * @param $dir
     * @return bool
     */
    function deltree($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }
}
