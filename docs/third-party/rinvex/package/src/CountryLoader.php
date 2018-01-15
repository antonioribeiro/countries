<?php

/*
 * NOTICE OF LICENSE
 *
 * Part of the Rinvex Country Package.
 *
 * This source file is subject to The MIT License (MIT)
 * that is bundled with this package in the LICENSE file.
 *
 * Package: Rinvex Country Package
 * License: The MIT License (MIT)
 * Link:    https://rinvex.com
 */

declare(strict_types=1);

namespace Rinvex\Country;

use Closure;

class CountryLoader
{
    /**
     * The countries array.
     *
     * @var array
     */
    protected static $countries;

    /**
     * Get the country by it's ISO 3166-1 alpha-2.
     *
     * @param string $code
     * @param bool   $hydrate
     *
     * @return \Rinvex\Country\Country|array
     */
    public static function country($code, $hydrate = true)
    {
        $code = mb_strtolower($code);

        if (! isset(static::$countries[$code])) {
            static::$countries[$code] = json_decode(static::getFile(__DIR__.'/../resources/data/'.$code.'.json'), true);
        }

        return $hydrate ? new Country(static::$countries[$code]) : static::$countries[$code];
    }

    /**
     * Get all countries short-listed.
     *
     * @param bool $longlist
     * @param bool $hydrate
     *
     * @return array
     */
    public static function countries($longlist = false, $hydrate = false)
    {
        $list = $longlist ? 'longlist' : 'shortlist';

        if (! isset(static::$countries[$list])) {
            static::$countries[$list] = json_decode(static::getFile(__DIR__.'/../resources/data/'.$list.'.json'), true);
        }

        return $hydrate ? array_map(function ($country) {
            return new Country($country);
        }, static::$countries[$list]) : static::$countries[$list];
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed  $operator
     * @param mixed  $value
     *
     * @return array
     */
    public static function where($key, $operator, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        if (! isset(static::$countries['longlist'])) {
            static::$countries['longlist'] = json_decode(static::getFile(__DIR__.'/../resources/data/longlist.json'), true);
        }

        return static::filter(static::$countries['longlist'], static::operatorForWhere($key, $operator, $value));
    }

    /**
     * Get an operator checker callback.
     *
     * @param string $key
     * @param string $operator
     * @param mixed  $value
     *
     * @return \Closure
     */
    protected static function operatorForWhere($key, $operator, $value)
    {
        return function ($item) use ($key, $operator, $value) {
            $retrieved = static::get($item, $key);

            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
            }
        };
    }

    /**
     * Run a filter over each of the items.
     *
     * @param array         $items
     * @param callable|null $callback
     *
     * @return array
     */
    protected static function filter($items, callable $callback = null)
    {
        if ($callback) {
            return array_filter($items, $callback, ARRAY_FILTER_USE_BOTH);
        }

        return array_filter($items);
    }

    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $default
     *
     * @return mixed
     */
    protected static function get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (($segment = array_shift($key)) !== null) {
            if ($segment === '*') {
                if (! is_array($target)) {
                    return $default instanceof Closure ? $default() : $default;
                }

                $result = static::pluck($target, $key);

                return in_array('*', $key) ? static::collapse($result) : $result;
            }

            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default instanceof Closure ? $default() : $default;
            }
        }

        return $target;
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param array             $array
     * @param string|array      $value
     * @param string|array|null $key
     *
     * @return array
     */
    protected static function pluck($array, $value, $key = null)
    {
        $results = [];

        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        foreach ($array as $item) {
            $itemValue = static::get($item, $value);

            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = static::get($item, $key);

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param array $array
     *
     * @return array
     */
    protected static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if (! is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * Get contents of the given file path.
     *
     * @param string $filePath
     *
     * @throws \Rinvex\Country\CountryLoaderException
     *
     * @return string
     */
    protected static function getFile($filePath)
    {
        if (! file_exists($filePath)) {
            throw CountryLoaderException::invalidCountry();
        }

        return file_get_contents($filePath);
    }
}
