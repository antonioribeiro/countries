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
