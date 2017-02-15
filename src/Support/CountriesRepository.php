<?php

namespace PragmaRX\Countries\Support;

use PragmaRX\Countries\Service;
use MLD\Converter\JsonConverter;

class CountriesRepository
{
    public $timezones;

    /**
     * Countries json.
     *
     * @var
     */
    public $countriesJson;

    /**
     * Currencies repository.
     *
     * @var CurrenciesRepository
     */
    public $currenciesRepository;

    /**
     * Cache instance.
     *
     * @var \PragmaRX\Countries\Support\Cache
     */
    public $cache;

    /**
     * Countries.
     *
     * @var array
     */
    public $countries = [];

    public $hydrator;

    /**
     * CountriesRepository constructor.
     * @param Cache $cache
     * @param CurrenciesRepository $currenciesRepository
     * @param Hydrator $hydrator
     */
    public function __construct(Cache $cache, CurrenciesRepository $currenciesRepository, Hydrator $hydrator)
    {
        $this->cache = $cache;

        $this->currenciesRepository = $currenciesRepository;

        $this->hydrator = $hydrator;

        $this->loadCountries();

        $this->loadTimezones();
    }

    /**
     * Call a method currencies collection.
     *
     * @param $name
     * @param $arguments
     * @return bool|mixed
     */
    public function call($name, $arguments)
    {
        if ($value = $this->getCached($keyParameters = [$name, $arguments])) {
            return $value;
        }

        $result = call_user_func_array([$this, $name], $arguments);

        if (config('countries.hydrate.before')) {
            $result = $this->hydrator->hydrate($result);
        }

        return $this->cache($keyParameters, $result);
    }


    /**
     * Make a collection.
     *
     * @param $country
     * @return Collection
     */
    public function collection($country)
    {
        return new Collection($country);
    }

    /**
     * Get json converter home directory.
     *
     * @return string
     */
    public function getJsonConverterHomeDir()
    {
        return getPackageSrcDir(JsonConverter::class);
    }

    /**
     * Get package home dir.
     *
     * @return string
     */
    public function getHomeDir()
    {
        return getClassDir(Service::class);
    }

    /**
     * Get states json for a country.
     *
     * @param $country
     * @return null|string
     */
    public function getStatesJson($country)
    {
        $file = $this->getHomeDir().
            DIRECTORY_SEPARATOR.
            'data'.
            DIRECTORY_SEPARATOR.
            'states'.
            DIRECTORY_SEPARATOR.
            strtolower($country['cca3']).'.json'
        ;

        if (file_exists($file)) {
            return file_get_contents($file);
        }

        return null;
    }

    /**
     *
     */
    public function loadCountries()
    {
        $this->countriesJson = json_decode($this->loadCountriesJson());
    }

    /**
     *
     */
    public function loadTimezones()
    {
        $this->timezones = json_decode($this->loadTimezonesJson(), true);
    }

    /**
     * @return string
     */
    public function loadCountriesJson()
    {
        return $this->readFile(
            $this->getJsonConverterHomeDir().
            DIRECTORY_SEPARATOR.
            'dist'.
            DIRECTORY_SEPARATOR.
            'countries.json'
        );
    }

    /**
     * @return string
     */
    public function loadTimezonesJson()
    {
        return $this->readFile(
            $this->getHomeDir().
            DIRECTORY_SEPARATOR.
            'data'.
            DIRECTORY_SEPARATOR.
            'timezones.json'
        );
    }

    /**
     * Get all countries.
     *
     * @return Collection
     */
    public function all()
    {
        return $this->collection($this->countriesJson);
    }

    /**
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, array $arguments = [])
    {
        return call_user_func_array([$this->all(), $name], $arguments);
    }

    /**
     * @param $country
     * @return array
     */
    public function makeAllFlags($country)
    {
        return [
            // https://www.flag-sprites.com/
            // https://github.com/LeoColomb/flag-sprites
            'sprite' => '<span class="flag flag-'.($flag = strtolower($country['cca2'])).'></span>',

            // https://github.com/lipis/flag-icon-css
            'flag-icon' => '<span class="flag-icon flag-icon-'.$flag.'"></span>',
            'flag-icon-squared' => '<span class="flag-icon flag-icon-'.$flag.' flag-icon-squared"></span>',

            // https://github.com/lafeber/world-flags-sprite
            'world-flags-sprite' => '<span class="flag '.$flag.'></span>',

            // Internal svg file
            'svg' => $this->getFlagSvg($country['cca3'])
        ];
    }

    /**
     * @param $country
     * @return string
     */
    public function getFlagSvg($country)
    {
        return file_get_contents(
            $this->getJsonConverterHomeDir().
            DIRECTORY_SEPARATOR.
            'data'.
            DIRECTORY_SEPARATOR.
            strtolower($country).'.svg'
        );
    }

    /**
     * @param $country
     * @return string
     */
    public function getGeometry($country)
    {
        return file_get_contents(
            $this->getJsonConverterHomeDir().
            DIRECTORY_SEPARATOR.
            'data'.
            DIRECTORY_SEPARATOR.
            strtolower($country['cca3']).'.geo.json'
        );
    }

    /**
     * @param $country
     * @return string
     */
    public function getTopology($country)
    {
        return file_get_contents(
            $this->getJsonConverterHomeDir().
            DIRECTORY_SEPARATOR.
            'data'.
            DIRECTORY_SEPARATOR.
            strtolower($country['cca3']).'.geo.json'
        );
    }

    /**
     * Get a cached value.
     *
     * @param $array
     * @return bool|mixed
     */
    public function getCached($array)
    {
        if (config('countries.cache.enabled')) {
            if ($value = $this->cache->get($this->cache->makeKey($array))) {
                return $value;
            }
        }

        return false;
    }

    /**
     * Cache a value.
     *
     * @param $keyParameters
     * @param $value
     * @return mixed
     */
    public function cache($keyParameters, $value)
    {
        if (config('countries.cache.enabled')) {
            $this->cache->set($this->cache->makeKey($keyParameters), $value);
        }

        return $value;
    }

    /**
     * Read a file.
     *
     * @param $filePath
     * @return string
     */
    public function readFile($filePath)
    {
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }

        return null;
    }

    public function hydrate($collection, $elements = null)
    {
        return $this->hydrator->hydrate($collection, $elements);
    }
}
