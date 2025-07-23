<?php

namespace PragmaRX\Countries\Package\Data;

use Illuminate\Support\Str;
use PragmaRX\Countries\Package\Services\Cache\Service as Cache;
use PragmaRX\Countries\Package\Services\Helper;
use PragmaRX\Countries\Package\Services\Hydrator;
use Psr\SimpleCache\CacheInterface as CacheContract;

class Repository
{
    /**
     * Timezones.
     *
     * @var mixed
     */
    public $timezones;

    /**
     * Countries json.
     *
     * @var \Illuminate\Support\Collection
     */
    public $countriesJson;

    /**
     * Countries.
     *
     * @var array
     */
    public $countries = [];

    /**
     * Hydrator.
     *
     * @var Hydrator
     */
    public $hydrator;

    /**
     * Helper.
     *
     * @var Helper
     */
    private $helper;

    /**
     * Cache.
     *
     * @var CacheContract
     */
    private $cache;

    /**
     * @var object
     */
    private $config;

    /**
     * Repository constructor.
     *
     * @param CacheContract $cache
     * @param Hydrator      $hydrator
     * @param Helper        $helper
     * @param object        $config
     */
    public function __construct(CacheContract $cache, Hydrator $hydrator, Helper $helper, $config)
    {
        $this->cache = $cache;

        $this->hydrator = $hydrator;

        $this->helper = $helper;

        $this->config = $config;
    }

    /**
     * Call magic method.
     *
     * @param       string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments = [])
    {
        return \call_user_func_array([$this->all(), $name], $arguments);
    }

    /**
     * Call a method currencies collection.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return bool|mixed
     */
    public function call(string $name, array $arguments)
    {
        $cacheKey = method_exists($this->cache, 'makeKey')
            ? $this->cache->makeKey($name, $arguments)
            : serialize([$name, $arguments]);
        if ($value = $this->cache->get($cacheKey)) {
            return $value;
        }

        $result = \call_user_func_array([$this, $name], $arguments);

        if ($this->config->get('hydrate.before')) {
            $result = $this->hydrator->hydrate($result);
        }

        $this->cache->set($cacheKey, $result, $this->config->get('cache.duration'));

        return $result;
    }

    /**
     * @return Helper
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Hydrator getter.
     *
     * @return Hydrator
     */
    public function getHydrator()
    {
        return $this->hydrator;
    }

    /**
     * Boot the repository.
     *
     * @return static
     */
    public function boot()
    {
        $this->loadCountries();

        return $this;
    }

    /**
     * Load countries.
     *
     * @return \Illuminate\Support\Collection
     */
    public function loadCountries()
    {
        $this->countriesJson = $this->loadCountriesJson();

        $overload = $this->helper
            ->loadJsonFiles($this->helper->dataDir('countries/overload'))
            ->mapWithKeys(function ($country, $code) {
                return [Str::upper($code) => $country];
            });

        $this->countriesJson = $this->countriesJson->merge($overload);

        return $this->countriesJson;
    }

    /**
     * Load countries json file.
     *
     * @throws \Exception
     *
     * @return \Illuminate\Support\Collection
     */
    public function loadCountriesJson()
    {
        $data = $this->helper->loadJson($fileName = $this->helper->dataDir('countries/default/_all_countries.json'));

        if ($data->isEmpty()) {
            throw new \Exception("Could not load countries from {$fileName}");
        }

        return $data;
    }

    /**
     * Load currency json file.
     *
     * @param string $code
     *
     * @return string
     */
    public function loadCurrenciesForCountry(string $code)
    {
        $currencies = $this->helper->loadJson(
            $this->helper->dataDir('currencies/default/' . strtolower($code) . '.json'),
        );

        return $currencies;
    }

    /**
     * Get all countries.
     *
     * @return \PragmaRX\Countries\Package\Support\Collection
     */
    public function all()
    {
        return countriesCollect($this->countriesJson);
    }

    /**
     * Get all currencies.
     *
     * @return \Illuminate\Support\Collection
     */
    public function currencies()
    {
        $currencies = $this->helper
            ->loadJsonFiles($this->helper->dataDir('currencies/default'))
            ->mapWithKeys(function ($country, $code) {
                return [Str::upper($code) => $country];
            });

        $overload = $this->helper
            ->loadJsonFiles($this->helper->dataDir('currencies/overload'))
            ->mapWithKeys(function ($country, $code) {
                return [Str::upper($code) => $country];
            });

        return $currencies->merge($overload);
    }

    /**
     * Make flags array for a coutry.
     *
     * @param array $country
     *
     * @return array
     */
    public function makeAllFlags(array $country)
    {
        return [
            // https://www.flag-sprites.com/
            // https://github.com/LeoColomb/flag-sprites
            'sprite' => '<span class="flag flag-' . ($cca3 = strtolower($country['cca3'])) . '"></span>',

            // https://github.com/lipis/flag-icon-css
            'flag-icon' =>
                '<span class="flag-icon flag-icon-' .
                ($iso_3166_1_alpha2 = strtolower($country['iso_3166_1_alpha2'] ?? '')) .
                '"></span>',
            'flag-icon-squared' =>
                '<span class="flag-icon flag-icon-' . $iso_3166_1_alpha2 . ' flag-icon-squared"></span>',

            // https://github.com/lafeber/world-flags-sprite
            'world-flags-sprite' => '<span class="flag ' . $cca3 . '"></span>',

            // Internal svg file
            'svg' => $this->getFlagSvg($country['cca3']),

            'svg_path' => $this->getFlagSvgPath($country['cca3']),
        ];
    }

    /**
     * Read the flag SVG file.
     *
     * @param string $country
     *
     * @return string
     */
    public function getFlagSvg(string $country)
    {
        return $this->helper->loadFile($this->getFlagSvgPath($country));
    }

    /**
     * Get the SVG file path.
     *
     * @param string $country
     *
     * @return string
     */
    public function getFlagSvgPath(string $country)
    {
        return $this->helper->dataDir('flags/' . strtolower($country) . '.svg');
    }

    /**
     * Get country geometry.
     *
     * @param string $country
     *
     * @return string
     */
    public function getGeometry(string $country)
    {
        return $this->helper->loadFile($this->helper->dataDir('geo/' . strtolower($country) . '.geo.json'));
    }

    /**
     * Get country topology.
     *
     * @param string $country
     *
     * @return string
     */
    public function getTopology(string $country)
    {
        return $this->helper->loadFile($this->helper->dataDir('topo/' . strtolower($country) . '.topo.json'));
    }

    /**
     * Hydrate a country element.
     *
     * @param      mixed $collection
     * @param null|array $elements
     *
     * @return \Illuminate\Support\Collection
     */
    public function hydrate($collection, $elements = null)
    {
        return $this->hydrator->hydrate($collection, $elements);
    }

    /**
     * Find a country timezone.
     *
     * @param string $countryCode
     *
     * @return \Illuminate\Support\Collection
     */
    public function findTimezones(string $countryCode)
    {
        return $this->helper->loadJson(
            $this->helper->dataDir('timezones/countries/default/' . strtolower($countryCode) . '.json'),
        );
    }

    /**
     * Find a country timezone.
     *
     * @param string $zoneId
     *
     * @return \Illuminate\Support\Collection
     */
    public function findTimezoneTime(string $zoneId)
    {
        return $this->helper->loadJson($this->helper->dataDir("timezones/timezones/default/{$zoneId}.json"));
    }
}
