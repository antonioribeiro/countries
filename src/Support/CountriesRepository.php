<?php

namespace PragmaRX\Countries\Support;

use Illuminate\Support\Str;
use PragmaRX\Countries\Service;
use MLD\Converter\JsonConverter;

class CountriesRepository
{
    /**
     * Countries json.
     *
     * @var
     */
    protected $countriesJson;

    /**
     * Currencies repository.
     *
     * @var CurrenciesRepository
     */
    protected $currenciesRepository;

    /**
     * Cache instance.
     *
     * @var \PragmaRX\Countries\Support\Cache
     */
    protected $cache;

    /**
     * Countries.
     *
     * @var array
     */
    protected $countries = [];

    /**
     * CountriesRepository constructor.
     * @param Cache $cache
     * @param CurrenciesRepository $currenciesRepository
     */
    public function __construct(Cache $cache, CurrenciesRepository $currenciesRepository)
    {
        $this->cache = $cache;

        $this->currenciesRepository = $currenciesRepository;

        $this->loadCountries();
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
            $result = $this->hydrate($result);
        }

        return $this->cache($keyParameters, $result);
    }

    /**
     * Make a collection.
     *
     * @param $country
     * @return CountriesCollection
     */
    public function collection($country)
    {
        return new CountriesCollection($country);
    }

    /**
     * Get json converter home directory.
     *
     * @return string
     */
    protected function getJsonConverterHomeDir()
    {
        return getPackageSrcDir(JsonConverter::class);
    }

    /**
     * Get package home dir.
     *
     * @return string
     */
    protected function getHomeDir()
    {
        return getClassDir(Service::class);
    }

    /**
     * Get states json for a country.
     *
     * @param $country
     * @return null|string
     */
    protected function getStatesJson($country)
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
     * Check if an element needs hydrated.
     *
     * @param $cc
     * @param $element
     * @return bool
     */
    protected function needsHydration($cc, $element)
    {
        if (! isset($this->countries[$cc]['hydrated'])) {
            $this->countries[$cc]['hydrated'] = [];
        }

        if (isset($this->countries[$cc]['hydrated'][$element])) {
            return false;
        }

        $hydrate = $this->countries[$cc]['hydrated'];

        $hydrate[$element] = true;

        $this->countries[$cc]['hydrated'] = $hydrate;

        return true;
    }

    /**
     * @param $country
     * @return CountriesCollection
     */
    protected function hydrateCollection($country)
    {
        if (! config('countries.hydrate.elements.collection')) {
            return $country;
        }

        return $this->collection($country);
    }

    /**
     * @param $country
     * @return mixed
     */
    protected function hydrateStates($country)
    {
        if (! config('countries.hydrate.elements.states')) {
            return $country;
        }

        $country['states'] = json_decode($this->getStatesJson($country), true);

        return $country;
    }

    /**
     * @param $country
     * @return mixed
     */
    protected function hydrateTopology($country)
    {
        if (! config('countries.hydrate.elements.topology')) {
            return $country;
        }

        $country['topology'] = $this->getTopology($country);

        return $country;
    }

    /**
     * @param $country
     * @return mixed
     */
    protected function hydrateGeometry($country)
    {
        if (! config('countries.hydrate.elements.geometry')) {
            return $country;
        }

        $country['geometry'] = $this->getGeometry($country);

        return $country;
    }

    /**
     *
     */
    protected function loadCountries()
    {
        $this->countriesJson = json_decode($this->loadCountriesJson());
    }

    /**
     * @return string
     */
    protected function loadCountriesJson()
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
     * Get all countries.
     *
     * @return CountriesCollection
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
            'sprite' => '<span class="flag flag-"'.($flag = strtolower($country['cca2'])).'></span>',

            // https://github.com/lipis/flag-icon-css
            'flag-icon' => '<span class="flag-icon flag-icon-'.$flag.'"></span>',
            'flag-icon-squared' => '<span class="flag-icon flag-icon-'.$flag.' flag-icon-squared"></span>',

            // https://github.com/lafeber/world-flags-sprite
            'world-flags-sprite' => '<span class="flag "'.$flag.'></span>',

            // Internal svg file
            'svg' => $this->getFlagSvg($country['cca3'])
        ];
    }

    /**
     * @param $country
     * @return string
     */
    protected function getFlagSvg($country)
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
    protected function getGeometry($country)
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
    protected function getTopology($country)
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
     * @return mixed
     */
    protected function hydrateFlag($country)
    {
        if (! config('countries.hydrate.elements.flag')) {
            return $country;
        }

        $country['flag'] = $this->makeAllFlags($country);

        return $country;
    }

    /**
     * @param $country
     * @return mixed
     */
    protected function hydrateBorders($country)
    {
        if (! config('countries.hydrate.elements.borders')) {
            return $country;
        }

        $country['borders'] = collect($country['borders'])->map(function($border) {
            return $this->call('where', ['cca3', $border]);
        });

        return $this->toArray($country);
    }

    /**
     * @param $country
     * @return mixed
     */
    protected function hydrateCurrency($country)
    {
        if (! config('countries.hydrate.elements.currency')) {
            return $country;
        }

        $country['currency'] = collect($country['currency'])->map(function($code) {
            return $this->currenciesRepository->loadCurrency($code);
        });

        return $this->toArray($country);
    }

    /**
     * Hidrate a countries collection with languages.
     *
     * @param CountriesCollection $countries
     * @param null $elements
     * @return CountriesCollection
     */
    public function hydrate(CountriesCollection $countries, $elements = null)
    {
        $elements = $elements ?: config('countries.hydrate.elements');

        return $this->collection(
            $countries->map(function($country) use ($elements) {
                $country = $this->toArray($country);

                if (! isset($this->countries[$cc = $country['cca3']])) {
                    $this->countries[$cc] = $country;
                }

                foreach ($elements as $element => $enabled) {
                    if (! $this->needsHydration($cc, $element)) {
                        $this->countries[$cc] = $this->{'hydrate'.Str::studly($element)}($this->countries[$cc]);
                    }
                }

                return $this->countries[$cc];
            })
        );
    }

    /**
     * Get a cached value.
     *
     * @param $array
     * @return bool|mixed
     */
    protected function getCached($array)
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
    protected function cache($keyParameters, $value)
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
    protected function readFile($filePath)
    {
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }

        return null;
    }

    /**
     * Transform a class into an array.
     *
     * @param $data
     * @return mixed
     */
    protected function toArray($data)
    {
        if ($data instanceof \stdClass) {
            $data = json_decode(json_encode($data), true);
        }

        return $data;
    }
}
