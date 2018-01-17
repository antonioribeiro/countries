<?php

use PragmaRX\Countries\Package\Support\Collection;

if (! function_exists('array_sort_by_keys_recursive')) {
    /**
     * Recursively sort array by keys.
     *
     * @param $array
     * @return array
     */
    function array_sort_by_keys_recursive(&$array)
    {
        if (is_array($array) || arrayable($array)) {
            $array = arrayable($array) ? $array->toArray() : $array;

            ksort($array);

            array_walk($array, 'array_sort_by_keys_recursive');
        }
    }
}

if (! function_exists('countriesCollect')) {
    /**
     * Check if array is multidimensional.
     *
     * @param mixed|null $data
     * @return \PragmaRX\Countries\Package\Support\Collection
     */
    function countriesCollect($data = null)
    {
        return new Collection($data);
    }
}

if (! function_exists('arrayable')) {
    /**
     * Recursively change all array keys case.
     *
     * @param $variable
     * @return bool
     */
    function arrayable($variable)
    {
        return is_object($variable) && method_exists($variable, 'toArray');
    }
}
