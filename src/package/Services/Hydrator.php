<?php

namespace PragmaRX\Countries\Package\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Hydrator
{
    /**
     * All hydrators.
     *
     * @var array
     */
    const HYDRATORS = [
        'borders',
        'cities',
        'collection',
        'countries',
        'country',
        'currencies',
        'flag',
        'geometry',
        'natural_earth_data',
        'states',
        'taxes',
        'timezones',
        'timezones_times',
        'topology',
    ];

    /**
     * Countries repository.
     *
     * @var \PragmaRX\Countries\Package\Data\Repository
     */
    protected $repository;

    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Hydrator constructor.
     *
     * @param object $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Can hydrate?
     *
     * @param string $element
     * @param bool $enabled
     * @param string $countryCode
     *
     * @return bool
     */
    protected function canHydrate(string $element, bool $enabled, string $countryCode)
    {
        return ($enabled || $this->config->get('hydrate.elements.' . $element)) &&
            !isset($this->repository->countries[$countryCode]['hydrated'][$element]);
    }

    /**
     * @param string $countryCode
     * @return array
     */
    protected function createHydrated(string $countryCode): array
    {
        if (!isset($this->repository->countries[$countryCode]['hydrated'])) {
            $this->repository->countries[$countryCode]['hydrated'] = [];
        }

        return $this->repository->countries[$countryCode]['hydrated'];
    }

    protected function fixCurrencies(array $country): array
    {
        if (!isset($country['currencies']) && isset($country['currency'])) {
            $country['currencies'] = $country['currency'];
        }

        return $country;
    }

    /**
     * Hydrate elements of a collection of countries.
     *
     * @param \Illuminate\Support\Collection $countries
     * @param array|null $elements
     *
     * @return mixed
     */
    private function hydrateCountries(\Illuminate\Support\Collection $countries, ?array $elements = null)
    {
        return countriesCollect(
            $countries->map(function ($country) use ($elements) {
                return $this->hydrateCountry(is_array($country) ? $country : $country->toArray(), $elements);
            }),
        );
    }

    /**
     * Hydrate elements of a country.
     *
     * @param array $country
     * @param array $elements
     *
     * @return mixed
     */
    private function hydrateCountry(array $country, array $elements)
    {
        $countryCode = $country['cca3'];

        $this->addCountry($countryCode, $country);

        foreach ($elements as $element => $enabled) {
            $this->hydrateCountryElement($countryCode, $element, $enabled);
        }

        return $this->getCountry($countryCode);
    }

    /**
     * Check if an element is a country.
     *
     * @param mixed $element
     *
     * @return bool
     */
    private function isCountry($element)
    {
        return ($element instanceof Collection || \is_array($element)) && isset($element['cca3']);
    }

    /**
     * Check it's a currencies array or code.
     *
     * @param mixed $data
     *
     * @return bool
     */
    private function isCurrenciesArray($data)
    {
        return \is_array($data) && isset($data['ISO4217Code']);
    }

    /**
     * Load the cities file and merge overloads.
     *
     * @param array $country
     *
     * @return mixed
     */
    private function loadCities(array $country)
    {
        return $this->repository
            ->getHelper()
            ->loadJson($country['cca3'], 'cities/default')
            ->merge($this->repository->getHelper()->loadJson($country['cca3'], 'cities/overload'));
    }

    /**
     * Load the states file and merge overloads.
     *
     * @param array $country
     *
     * @return mixed
     */
    private function loadStates(array $country)
    {
        return $this->repository
            ->getHelper()
            ->loadJson($country['cca3'], 'states/default')
            ->merge($this->repository->getHelper()->loadJson($country['cca3'], 'states/overload'));
    }

    /**
     * Load the taxes file and merge overloads.
     *
     * @param array $country
     *
     * @return mixed
     */
    private function loadTaxes(array $country)
    {
        return $this->repository
            ->getHelper()
            ->loadJson($country['cca3'], 'taxes/default')
            ->merge($this->repository->getHelper()->loadJson($country['cca3'], 'taxes/overload'));
    }

    /**
     * Check if an element needs hydrated.
     *
     * @param      string $countryCode
     * @param      string $element
     * @param bool $enabled
     *
     * @return bool
     */
    protected function needsHydration(string $countryCode, string $element, bool $enabled = false)
    {
        if (!$this->canHydrate($element, $enabled, $countryCode)) {
            return false;
        }

        return $this->updateHydrated($countryCode, $element);
    }

    /**
     * Hydrate cities.
     *
     * @param array $country
     *
     * @return mixed
     */
    public function hydrateCities(array $country)
    {
        $country['cities'] = $this->loadCities($country);

        return $country;
    }

    /**
     * Hydrate states.
     *
     * @param array $country
     *
     * @return mixed
     */
    public function hydrateStates(array $country)
    {
        $country['states'] = $this->loadStates($country);

        return $country;
    }

    /**
     * Hydrate taxes.
     *
     * @param array $country
     *
     * @return array
     */
    public function hydrateTaxes(array $country)
    {
        $country['taxes'] = $this->loadTaxes($country);

        return $country;
    }

    /**
     * Hydrate topoloy.
     *
     * @param array $country
     *
     * @return mixed
     */
    public function hydrateTopology(array $country)
    {
        $country['topology'] = $this->repository->getTopology($country['cca3']);

        return $country;
    }

    /**
     * Hydrate geometry.
     *
     * @param array $country
     *
     * @return mixed
     */
    public function hydrateGeometry(array $country)
    {
        $country['geometry'] = $this->repository->getGeometry($country['cca3']);

        return $country;
    }

    /**
     * Get hydration elements.
     *
     * @param mixed $elements
     *
     * @return array|string|mixed
     */
    protected function getHydrationElements($elements)
    {
        $elements = $elements ?: $this->config->get('hydrate.elements');

        if (\is_string($elements) || is_numeric($elements)) {
            return [$elements => true];
        }

        return $this->checkHydrationElements($elements);
    }

    /**
     * Hydrate flag.
     *
     * @param array $country
     *
     * @return mixed
     */
    public function hydrateFlag(array $country)
    {
        $country = countriesCollect($country)->merge(['flag' => $this->repository->makeAllFlags($country)]);

        return $country;
    }

    /**
     * Hydrate borders.
     *
     * @param array $country
     *
     * @return mixed
     */
    public function hydrateBorders(array $country)
    {
        $country['borders'] = isset($country['borders'])
            ? ($country['borders'] = countriesCollect($country['borders'])->map(function ($border) {
                return $this->repository->call('where', ['cca3', $border])->first();
            }))
            : countriesCollect();

        return $country;
    }

    /**
     * Hydrate timezones.
     *
     * @param array $country
     *
     * @return mixed
     */
    public function hydrateTimezones(array $country)
    {
        return countriesCollect($country)->merge(['timezones' => $this->repository->findTimezones($country['cca3'])]);
    }

    /**
     * Hydrate all times for a country timezones.
     *
     * @param array $country
     *
     * @return mixed
     */
    public function hydrateTimezonesTimes(array $country)
    {
        $country = $this->hydrateTimezones($country);

        $country['timezones'] = $country->timezones->map(function ($timezone) {
            return countriesCollect($timezone)->merge([
                'times' => $this->repository->findTimezoneTime($timezone['zone_id']),
            ]);
        });

        return $country;
    }

    /**
     * Hydrate currencies.
     *
     * @param array $country
     *
     * @return mixed
     */
    public function hydrateCurrencies(array $country)
    {
        $currencies = [];

        $country = $this->fixCurrencies($country);

        if (isset($country['currencies'])) {
            $currencies = countriesCollect($country['currencies'])->mapWithKeys(function ($code, $currencyCode) {
                if ($this->isCurrenciesArray($code)) {
                    return [
                        $code['ISO4217Code'] => $code,
                    ];
                }

                if (is_object($code)) {
                    $code = $currencyCode;
                }

                // Ensure $code is a string before passing to loadCurrenciesForCountry
                if (is_array($code)) {
                    return [];
                }

                return [
                    $code => $this->repository->loadCurrenciesForCountry($code),
                ];
            });
        }

        $country['currencies'] = $currencies;

        return $country;
    }

    /**
     * Hydrate a countries collection with languages.
     *
     * @param \Illuminate\Support\Collection|array|\stdClass $target
     * @param null                                           $elements
     *
     * @return \Illuminate\Support\Collection
     */
    public function hydrate($target, $elements = null)
    {
        $elements = $this->getHydrationElements($elements);

        if (countriesCollect($elements)->count() === 0) {
            return $target;
        }

        return $this->isCountry($target->toArray())
            ? $this->hydrateCountry(
                is_array($target) ? $target : $target->toArray(),
                is_array($elements) ? $elements : $elements->toArray(),
            )
            : $this->hydrateCountries($target, is_array($elements) ? $elements : $elements->toArray());
    }

    /**
     * Get country by country code.
     *
     * @param string $countryCode
     *
     * @return mixed
     */
    public function getCountry(string $countryCode)
    {
        return countriesCollect($this->repository->countries[$countryCode]);
    }

    /**
     * Check and create a country in the repository.
     *
     * @param string $countryCode
     * @param array $country
     */
    public function addCountry(string $countryCode, array $country): void
    {
        if (!isset($this->repository->countries[$countryCode])) {
            $this->repository->countries[$countryCode] = $country;
        }
    }

    /**
     * Hydrate a country element.
     *
     * @param string $countryCode
     * @param string $element
     * @param bool $enabled
     */
    public function hydrateCountryElement(string $countryCode, string $element, bool $enabled): void
    {
        if ($this->needsHydration($countryCode, $element, $enabled)) {
            $country = $this->repository->countries[$countryCode];
            $countryArray = is_array($country) ? $country : $country->toArray();
            $this->repository->countries[$countryCode] = $this->{'hydrate' . Str::studly($element)}($countryArray);
        }
    }

    /**
     * Check hydration elements.
     *
     * @param mixed $elements
     *
     * @return \PragmaRX\Countries\Package\Support\Collection
     */
    protected function checkHydrationElements($elements)
    {
        $elements = countriesCollect($elements)
            ->mapWithKeys(function ($value, $key) {
                if (is_numeric($key)) {
                    $key = $value;
                    $value = true;
                }

                return [$key => $value];
            })
            ->filter(function ($element) {
                return $element;
            });

        return $elements;
    }

    /**
     * Repository setter.
     *
     * @param \PragmaRX\Countries\Package\Data\Repository $repository
     */
    public function setRepository($repository): void
    {
        $this->repository = $repository;
    }

    /**
     * Update hydrated.
     *
     * @param string $countryCode
     * @param string $element
     *
     * @return bool
     */
    protected function updateHydrated(string $countryCode, string $element)
    {
        $hydrated = $this->createHydrated($countryCode);

        $hydrated[$element] = true;

        $this->repository->countries[$countryCode]['hydrated'] = $hydrated;

        return true;
    }
}
