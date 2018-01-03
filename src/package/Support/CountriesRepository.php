<?php

namespace PragmaRX\Countries\Package\Support;

use MLD\Converter\JsonConverter;

class CountriesRepository extends Base
{
    /**
     * Timezones.
     *
     * @var
     */
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
     * CountriesRepository constructor.
     *
     * @param Cache $cache
     * @param CurrenciesRepository $currenciesRepository
     * @param Hydrator $hydrator
     */
    public function __construct(Cache $cache, CurrenciesRepository $currenciesRepository, Hydrator $hydrator)
    {
        $this->setCache($cache);

        $this->currenciesRepository = $currenciesRepository;

        $this->hydrator = $hydrator;

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
            $result = $this->hydrator->hydrate($result);
        }

        return $this->cache($keyParameters, $result);
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
     * Get json converter home directory.
     *
     * @return string
     */
    public function getJsonConverterHomeDir()
    {
        return getPackageSrcDir(JsonConverter::class);
    }

    /**
     * Load countries.
     */
    public function loadCountries()
    {
        $this->countriesJson = json_decode($this->loadCountriesJson());
    }

    /**
     * Load timezones.
     */
    public function loadTimezones()
    {
        if (is_null($this->timezones)) {
            $this->timezones = require $this->getTimezoneFilename();
        }
    }

    /**
     * Load countries json file.
     *
     * @return string
     */
    public function loadCountriesJson()
    {
        return $this->loadFile(
            $this->dataDir('countries/default/_all_countries.json')
        );
    }

    /**
     * Load timezones json file.
     *
     * @return string
     */
    public function getTimezoneFilename()
    {
        return $this->dataDir('/timezones.php');
    }

    /**
     * Get all countries.
     *
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function all()
    {
        return $this->collection($this->countriesJson);
    }

    /**
     * Get all currencies.
     *
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function currencies()
    {
        return $this->currenciesRepository->all();
    }

    /**
     * Call magic method.
     *
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, array $arguments = [])
    {
        return call_user_func_array([$this->all(), $name], $arguments);
    }

    /**
     * Make flags array for a coutry.
     *
     * @param $country
     * @return array
     */
    public function makeAllFlags($country)
    {
        return [
            // https://www.flag-sprites.com/
            // https://github.com/LeoColomb/flag-sprites
            'sprite' => '<span class="flag flag-'.($flag = strtolower($country['cca3'])).'"></span>',

            // https://github.com/lipis/flag-icon-css
            'flag-icon' => '<span class="flag-icon flag-icon-'.$flag.'"></span>',
            'flag-icon-squared' => '<span class="flag-icon flag-icon-'.$flag.' flag-icon-squared"></span>',

            // https://github.com/lafeber/world-flags-sprite
            'world-flags-sprite' => '<span class="flag '.$flag.'"></span>',
            
            // Internal svg file
            'svg' => $this->getFlagSvg($country['cca3']),
        ];
    }

    /**
     * Read the flag svg file.
     *
     * @param $country
     * @return string
     */
    public function getFlagSvg($country)
    {
        return $this->loadFile(
            $this->getJsonConverterHomeDir().
            _dir('/data/').
            strtolower($country).'.svg'
        );
    }

    /**
     * Get country geometry.
     *
     * @param $country
     * @return string
     */
    public function getGeometry($country)
    {
        return $this->loadFile(
            $this->getJsonConverterHomeDir().
            _dir('/data/').
            strtolower($country['cca3']).'.geo.json'
        );
    }

    /**
     * Get country topology.
     *
     * @param $country
     * @return string
     */
    public function getTopology($country)
    {
        return $this->loadFile(
            $this->getJsonConverterHomeDir().
            _dir('/data/').
            strtolower($country['cca3']).'.geo.json'
        );
    }

    /**
     * Hydrate a country element.
     *
     * @param $collection
     * @param null $elements
     * @return \PragmaRX\Coollection\Package\Coollection
     */
    public function hydrate($collection, $elements = null)
    {
        return $this->hydrator->hydrate($collection, $elements);
    }

    /**
     * Find a country timezone.
     *
     * @param $countryCode
     * @return null
     */
    public function findTimezone($countryCode)
    {
        $this->loadTimezones();

        return isset($this->timezones[$countryCode])
            ? $this->timezones[$countryCode]
            : null;
    }
}
