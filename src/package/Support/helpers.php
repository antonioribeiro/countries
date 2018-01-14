<?php

use PragmaRX\Coollection\Package\Coollection;
use ShapeFile\ShapeFile;
use PragmaRX\Countries\Package\Support\Collection;
use GuzzleHttp\Client as Guzzle;

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
     * @return \PragmaRX\Countries\Package\Support\Collection
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
     */
    function download_file($url, $destination)
    {
        if (file_exists($destination)) {
            return;
        }

        try {
            download_fopen($url, $destination);
        } catch (\Exception $exception) {
            download_curl($url, $destination);
        }

        chmod($destination, 0644);
    }
}

if (! function_exists('download_fopen')) {
    /**
     * Download a file from the Internet using fopen (faster).
     *
     * @param $url
     * @param $destination
     */
    function download_fopen($url, $destination)
    {
        $fr = fopen($url, 'r');

        $fw = fopen($destination, 'w');

        while (! feof($fr)) {
            fwrite($fw, fread($fr, 4096));
            flush();
        }

        fclose($fr);

        fclose($fw);
    }
}

if (! function_exists('download_curl')) {
    /**
     * Download a file from the Internet using curl (slower).
     *
     * @param $url
     * @param $destination
     */
    function download_curl($url, $destination)
    {
        $nextStep = 8192;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $total, $downloaded) use (&$nextStep) {
            if ($downloaded > $nextStep) {
                echo ".";
                $nextStep += 8192;
            }
        });
        curl_setopt($ch, CURLOPT_NOPROGRESS, false); // needed to make progress function work
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "GuzzleHttp/6.2.1 curl/7.54.0 PHP/7.2.0");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        file_put_contents($destination, curl_exec($ch));
        curl_close($ch);

        echo "\n";
    }
}

if (! function_exists('unzip')) {
    /**
     * Download a file from the Internet.
     *
     * @param $file
     * @param $subPath
     */
    function unzip($file, $subPath)
    {
        $path = dirname($file);

        $exclude = basename($file);

        if (!ends_with($file, '.zip') || file_exists($subPath = "$path/$subPath")) {
            return;
        }

        chdir($path);

        exec("unzip -o $file");

        if (ends_with('master.zip', $file)) {
            $dir = countriesCollect(scandir($path))->filter(function($file) use ($exclude) {
                return $file !== '.' && $file !== '..' && $file !== $exclude;
            })->first();

            rename("$path/$dir", $subPath);
        }
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
        if (! file_exists($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }
}

if (! function_exists('load_shapefile')) {
    /**
     * Load a shapefile.
     *
     * @param $dir
     * @return \PragmaRX\Countries\Package\Support\Collection
     */
    function load_shapefile($dir)
    {
        dump(1);
        $shapeRecords = new ShapeFile($dir);

        $result = [];

        foreach ($shapeRecords as $record) {
            if ($record['dbf']['_deleted']) {
                continue;
            }

            $data = $record['dbf'];

            unset($data['_deleted']);

            $result[] = $data;

            dd($result);
        }

        unset($shapeRecords);

        return countriesCollect($result)->mapWithKeys(function ($fields, $key1) {
            return [
                strtolower($key1) => countriesCollect($fields)->mapWithKeys(function ($value, $key2) {
                    return [strtolower($key2) => $value];
                }),
            ];
        });
    }
}

if (! function_exists('array_keys_snake_recursive')) {
    /**
     * Recursively change all array keys case.
     *
     * @param $array
     * @return Collection
     */
    function array_keys_snake_recursive($array)
    {
        $result = [];

        $array = arrayable($array) ? $array->toArray() : $array;

        array_walk($array, function ($value, $key) use (&$result) {
            $result[snake_case($key)] = arrayable($value) || is_array($value)
                                ? array_keys_snake_recursive($value)
                                : $value;
        });

        return countriesCollect($result);
    }
}

if (! function_exists('arrayable')) {
    /**
     * Recursively change all array keys case.
     *
     * @param $variable
     * @return boolean
     */
    function arrayable($variable)
    {
        return is_object($variable) && method_exists($variable, 'toArray');
    }
}

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

if (! function_exists('fix_utf8')) {
    /**
     * Fix a bad UTF8 string.
     *
     * @param $string
     * @return string
     */
    function fix_utf8($string)
    {
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $string);
    }
}

if (! function_exists('csv_decode')) {
    /**
     * Load CSV file.
     *
     * @param $csv
     * @return Coollection
     */
    function csv_decode($csv)
    {
        return countriesCollect(array_map('str_getcsv', $csv));
    }
}
