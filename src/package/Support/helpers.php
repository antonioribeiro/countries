<?php

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

    while (! file_exists($dir.DIRECTORY_SEPARATOR.'composer.json') && $depth < 16) {
        $dir .= DIRECTORY_SEPARATOR.'..';

        $depth++;
    }

    return $dir;
}

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

/**
 * Check if array is multidimensional.
 *
 * @param $item
 * @return bool
 */
function array_is_multidimensional($item)
{
    if (! is_array($item)) {
        return false;
    }

    $rv = array_filter($item, 'is_array');

    if (count($rv) > 0) {
        return true;
    }

    return false;
}
